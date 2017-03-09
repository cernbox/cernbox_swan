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

	public function __construct($AppName, IRequest $request){
		parent::__construct($AppName, $request);
		$this->secret = \OC::$server->getConfig()->getSystemValue("cernboxswan.secret", "passwordhere123");
	}

	/**
	 * This method will create shared link for SWAN
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @PublicPage
	 */
	public function createLink($owner = null, $file = null, $secret = null) {
		if(!$secret) {
			return new DataResponse(['error' => 'secret is not set']);
		}

		if(!$owner) {
			return new DataResponse(['error' => 'owner is not set']);
		}

		if(!$file) {
			return new DataResponse(['error' => 'file is not set']);
		}

		// check that secrets match
		if($secret !== $this->secret) {
			return new DataResponse(['error' => 'secrets do not match']);
		}

		$user = \OC::$server->getUserManager()->get($owner);
		if($user === null) {
			return new DataResponse(['error' => 'owner does not exist']);
		}

		// set user in session
		\OC_User::setUserId($user->getUID());
		$folder = \OC::$server->getUserFolder($user->getUID());
		if(!$folder) {
			return new DataResponse(['error' => 'cannot access user home folder']);
		}

		try {
			$node= $folder->get("testfileword.pdf");
		} catch(\OCP\Files\NotFoundException $e) {
			return new DataResponse(['error' => 'file not found']);
		}

		$type = 'file';
		$share = \OCP\Share::getItemSharedWithByLink($type, $node->getId(), $owner);

		if(!$share) {
			// we share it
			$token = \OCP\Share::shareItem($type, $node->getId(), 3, "", 1);
			if(!is_string($token)) {
				return new DataResponse(['error' => 'error sharing file']);
			}
		} else {
			$token = $share['token'];
		}
		$url = \OCP\Util::linkToPublic('files&t='.$token);
		return new DataResponse(['url' => $url]);
	}
}
