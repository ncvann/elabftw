<?php
/**
 * \Elabftw\Elabftw\Revisions
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use PDO;
use Exception;

/**
 * All about the revisions
 */
class Revisions
{
    /** pdo object */
    private $pdo;

    /** experiments or items */
    private $type;

    /** id of the item/exp */
    private $id;

    /** our current user */
    private $userid;

    /**
     * Constructor
     *
     * @param string $type
     * @param int $id
     * @param int $userid
     */
    public function __construct($type, $id, $userid)
    {
        $this->pdo = Db::getConnection();

        $this->type = $type;
        $this->id = $id;
        $this->userid = $userid;
    }

    /**
     * Add a revision
     *
     * @param string $body
     * @return bool
     */
    public function create($body)
    {
        if ($this->type === 'experiments') {
            $sql = "INSERT INTO experiments_revisions (item_id, body, userid) VALUES(:item_id, :body, :userid)";
        } else {
            $sql = "INSERT INTO items_revisions (item_id, body, userid) VALUES(:item_id, :body, :userid)";
        }

        $req = $this->pdo->prepare($sql);
        $req->bindParam(':item_id', $this->id);
        $req->bindParam(':body', $body);
        $req->bindParam(':userid', $this->userid);

        return $req->execute();
    }

    /**
     * Get how many revisions we have
     *
     */
    public function readCount()
    {
        if ($this->type === 'experiments') {
            $sql = "SELECT COUNT(*) FROM experiments_revisions
                WHERE item_id = :item_id ORDER BY savedate DESC";
        } else {
            $sql = "SELECT COUNT(*) FROM items_revisions
                WHERE item_id = :item_id ORDER BY savedate DESC";
        }
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':item_id', $this->id);
        $req->execute();

        return (int) $req->fetchColumn();
    }


    /**
     * Output HTML for displaying revisions
     *
     */
    public function showCount()
    {
        $html = '';
        $count = $this->readCount();

        if ($count > 0) {
            $html .= "<span class='align_right'>";
            $html .= $count . " " . ngettext('revision available.', 'revisions available.', $count);
            $html .= " <a href='revisions.php?type=" . $this->type . "&item_id=" . $this->id . "'>" . _('Show history') . "</a>";
            $html .= "</span>";
        }

        return $html;
    }

    /**
     * Read all revisions for an item
     *
     * @return array
     */
    public function read()
    {
        $sql = "SELECT * FROM " . $this->type . "_revisions WHERE item_id = :item_id AND userid = :userid ORDER BY savedate DESC";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':item_id', $this->id);
        $req->bindParam(':userid', $this->userid);
        $req->execute();

        return $req->fetchAll();
    }

    /**
     * Get the body of a revision
     *
     * @param int $revId The id of the revision
     * @return array
     */
    private function readRev($revId)
    {
        $sql = "SELECT body FROM " . $this->type . "_revisions WHERE id = :rev_id AND userid = :userid";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':rev_id', $revId);
        $req->bindParam(':userid', $this->userid);
        $req->execute();

        return $req->fetchColumn();
    }

    /**
     * Check if item is locked before restoring it
     *
     * @throws Exception
     * @return bool
     */
    private function isLocked()
    {
        $sql = "SELECT locked FROM " . $this->type . " WHERE id = :id";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->execute();
        $locked = $req->fetch();

        return $locked['locked'] == 1;
    }

    /**
     * Restore a revision
     *
     * @param int $revId The id of the revision we want to restore
     * @throws Exception
     * @return bool
     */
    public function restore($revId)
    {
        // check for lock
        if ($this->isLocked()) {
            throw new Exception(_('You cannot restore a revision of a locked item!'));
        }

        $body = $this->readRev($revId);

        $sql = "UPDATE " . $this->type . " SET body = :body WHERE id = :id";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':body', $body);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);

        return $req->execute();
    }
}
