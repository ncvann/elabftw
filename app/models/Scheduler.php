<?php
/**
 * \Elabftw\Elabftw\Scheduler
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

/**
 * All about the team's scheduler
 */
class Scheduler extends Entity
{
    /** pdo object */
    protected $pdo;

    /** id of the event */
    public $id;

    /**
     * Constructor
     *
     * @param int $team
     */
    public function __construct($team)
    {
        $this->team = $team;
        $this->pdo = Db::getConnection();
    }

    /**
     * Add an event for an item in the team
     *
     * @param int $item our selected item
     * @param string $start 2016-07-22T13:37:00
     * @param string $end 2016-07-22T19:42:00
     * @param string $title the comment entered by user
     * @param int $userid
     * @return bool
     */
    public function create($item, $start, $end, $title, $userid)
    {
        $sql = "INSERT INTO team_events(team, item, start, end, userid, title)
            VALUES(:team, :item, :start, :end, :userid, :title)";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':team', $this->team);
        $req->bindParam(':item', $item);
        $req->bindParam(':start', $start);
        $req->bindParam(':end', $end);
        $req->bindParam(':title', $title);
        $req->bindParam(':userid', $userid);

        return $req->execute();
    }

    /**
     * Return a JSON string with events for this item
     *
     * @return string JSON
     */
    public function read()
    {
        // the title of the event is Firstname + Lastname of the user who booked it
        $sql = "SELECT team_events.*,
            CONCAT(team_events.title, ' (', u.firstname, ' ', u.lastname, ')') AS title
            FROM team_events
            LEFT JOIN users AS u ON team_events.userid = u.userid
            WHERE team_events.team = :team AND team_events.item = :item";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':team', $this->team);
        $req->bindParam(':item', $this->id);
        $req->execute();

        return json_encode($req->fetchall());
    }

    /**
     * Update the start of an event (when you drag and drop it)
     *
     * @param string $start 2016-07-22T13:37:00
     * @return bool
     */
    public function updateStart($start)
    {
        $sql = "UPDATE team_events SET start = :start WHERE team = :team AND id = :id";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':start', $start);
        $req->bindParam(':team', $this->team);
        $req->bindParam(':id', $this->id);

        return $req->execute();
    }

    /**
     * Update the end of an event (when you resize it)
     *
     * @param string $end 2016-07-22T13:37:00
     * @return bool
     */
    public function updateEnd($end)
    {
        $sql = "UPDATE team_events SET end = :end WHERE team = :team AND id = :id";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':end', $end);
        $req->bindParam(':team', $this->team);
        $req->bindParam(':id', $this->id);

        return $req->execute();
    }

    /**
     * Remove an event
     *
     * @return bool
     */
    public function destroy()
    {
        $sql = "DELETE FROM team_events WHERE id = :id";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':id', $this->id);

        return $req->execute();
    }
}
