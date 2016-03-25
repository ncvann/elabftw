<?php
/**
 * app/controllers/UploadsController.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use \Exception;

/**
 * Uploads
 *
 */
require_once '../../inc/common.php';

try {

    // DESTROY
    if (isset($_POST['uploadsDestroy'])) {
        $Uploads = new Uploads($_POST['type'], $_POST['item_id'], $_POST['id']);
        if ($Uploads->destroy()) {
            echo '1';
        } else {
            echo '0';
        }
    }

} catch (Exception $e) {
    dblog('Error', $_SESSION['userid'], $e->getMessage());
    echo $e->getMessage();
}
