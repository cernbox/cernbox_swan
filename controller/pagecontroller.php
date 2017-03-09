<?php
/**
 * ownCloud - cernboxswan
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Hugo Gonzalez Labrador <hugo.gonzalez.labrador@cern.ch>
 * @copyright Hugo Gonzalez Labrador 2017
 */

namespace OCA\CernboxSwan\Controller;

use OCP\IRequest;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;

class PageController extends Controller {


	private $secret;
	private $eosPrefix;

	public function __construct($AppName, IRequest $request) {
		parent::__construct($AppName, $request);
		$this->secret = \OC::$server->getConfig()->getSystemValue("cernboxswan.secret", "passwordhere123");
		$this->eosPrefix = \OC::$server->getConfig()->getSystemValue("eos_prefix");
	}

	/**
	 * This method will create shared link for SWAN
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @PublicPage
	 */
	public function createLink($file = null, $secret = null) {
		if (!$secret) {
			return new DataResponse(['error' => 'secret is not set']);
		}

		if (!$file) {
			return new DataResponse(['error' => 'file is not set']);
		}

		// remove eos prefix from filename to make it relative to user directory
		$fileWithoutPrefix = trim(substr($file, strlen($this->eosPrefix)), '/');
		$parts = explode('/', $fileWithoutPrefix);
		array_shift($parts); // remove letter
		array_shift($parts); // remove username
		$file = trim(implode('/', $parts), '/');
		if(empty($file)) {
			return new DataResponse(['error' => 'cannot share home folder']);
		}

		// check that secrets match
		if ($secret !== $this->secret) {
			return new DataResponse(['error' => 'secrets do not match']);
		}

		$user = \OC::$server->getUserSession()->getUser();
		if ($user === null) {
			return new DataResponse(['error' => 'owner does not exist']);
		}

		$folder = \OC::$server->getUserFolder($user->getUID());
		if (!$folder) {
			return new DataResponse(['error' => 'cannot access user home folder']);
		}

		try {
			$node = $folder->get($file);
		} catch (\OCP\Files\NotFoundException $e) {
			return new DataResponse(['error' => 'file not found']);
		}

		if($node->getType() !== \OCP\Files\File::TYPE_FILE) {
			return new DataResponse(['error' => 'resource is not a file']);
		}

		$type = 'file';
		$share = \OCP\Share::getItemSharedWithByLink($type, $node->getId(), $owner);

		if (!$share) {
			// we share it
			$token = \OCP\Share::shareItem($type, $node->getId(), 3, "", 1);
			if (!is_string($token)) {
				return new DataResponse(['error' => 'error sharing file']);
			}
		} else {
			$token = $share['token'];
		}
		$url = \OCP\Util::linkToPublic('files&t=' . $token);
		return new DataResponse(['url' => $url]);
	}
}
