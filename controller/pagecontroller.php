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
	private $relyOnSSO;

	public function __construct($AppName, IRequest $request){
		parent::__construct($AppName, $request);
		$this->secret = \OC::$server->getConfig()->getSystemValue("cernboxswan.secret", "changeme!!!");
		$this->relyOnSSO = \OC::$server->getConfig()->getSystemValue("cernboxswan.relyonsso", true);
	}

	/**
	 * This method will create shared link for SWAN
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @PublicPage
	 */
	public function createLink($owner = null, $file) {
		if($this->relyOnSSO === true) {
			if($_SERVER["ADFS_LOGIN"] && is_string($_SERVER["ADFS_LOGIN"])) {
				$owner = $_SERVER["ADFS_LOGIN"];
			}
		}

		if(!$owner) {
			return new DataResponse(['error' => 'owner is not set', 'relyonsso' => $this->relyOnSSO]);
		}

		return new DataResponse(['owner' => $owner, 'file' => $file]);
	}
}
