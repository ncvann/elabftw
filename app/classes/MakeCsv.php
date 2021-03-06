<?php
/**
 * \Elabftw\Elabftw\MakeCsv
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
 * Make a CSV file from a list of id and a type
 */
class MakeCsv extends Make
{
    /** our pdo object */
    protected $pdo;

    /** a sha512 sum */
    public $fileName;
    /** the full path of the file */
    public $filePath;

    /** the lines in the csv file */
    private $list = array();
    /** the input ids */
    private $idList;
    /** the input ids but in an array */
    private $idArr = array();
    /** 'experiment' or 'items' */
    protected $type;
    /** the content */
    private $data;
    /** the url of the item */
    private $url;

    /**
     * Give me a list of id+id+id and a type, I make good csv for you
     *
     * @param string $idList 1+4+5+2
     * @param string $type 'experiments' or 'items'
     */
    public function __construct($idList, $type)
    {
        $this->pdo = Db::getConnection();

        $this->fileName = $this->getFileName();
        $this->filePath = $this->getTempFilePath($this->fileName);

        $this->idList = $idList;

        // assign and check type
        $this->type = $this->checkType($type);

        // set the column names
        $this->list[] = $this->populateFirstLine();

        // main loop
        $this->loopIdArr();
    }

    /**
     * Return a nice name for the file
     *
     * @return string
     */
    public function getCleanName()
    {
        return 'export.elabftw.csv';
    }
    /**
     * Here we populate the first row: it will be the column names
     *
     */
    private function populateFirstLine()
    {
        if ($this->type === 'experiments') {
            return array('id', 'date', 'title', 'content', 'status', 'elabid', 'url');
        }
        return  array('title', 'description', 'id', 'date', 'type', 'rating', 'url');
    }

    /**
     * Main loop
     *
     */
    private function loopIdArr()
    {
        $this->idArr = explode(" ", $this->idList);
        foreach ($this->idArr as $id) {
            if (!Tools::checkId($id)) {
                throw new Exception('Bad id.');
            }
            $this->checkVisibility($id);
            $this->initData($id);
            $this->setUrl($id);
            $this->addLine();
        }
        $this->writeCsv();
    }

    /**
     * Get data about the item
     *
     * @param int $id The id of the current item
     */
    private function initData($id)
    {
        if ($this->type === 'experiments') {
            $sql = "SELECT experiments.*,
                status.name AS statusname
                FROM experiments
                LEFT JOIN status ON (experiments.status = status.id)
                WHERE experiments.id = :id";
        } else {
            $sql = "SELECT items.*,
                items_types.name AS typename
                FROM items
                LEFT JOIN items_types ON (items.type = items_types.id)
                WHERE items.id = :id";
        }

        $req = $this->pdo->prepare($sql);
        $req->bindParam(':id', $id, \PDO::PARAM_INT);
        $req->execute();
        $this->data = $req->fetch();
    }

    /**
     * Construct URL
     *
     * @param int $id The id of the current item
     */
    private function setUrl($id)
    {
        $url = 'https://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . $_SERVER['PHP_SELF'];
        $needle = array('make.php', 'app/controllers/ExperimentsController.php');

        if ($this->type === 'experiments') {
            $url = str_replace($needle, 'experiments.php', $url);
        } else { //item
            $url = str_replace($needle, 'database.php', $url);
        }
        $this->url = $url . "?mode=view&id=" . $id;
    }

    /**
     * The column names will be different depending on type
     */
    private function addLine()
    {
        if ($this->type === 'experiments') {
            $this->list[] = array(
                $this->data['id'],
                $this->data['date'],
                htmlspecialchars_decode($this->data['title'], ENT_QUOTES | ENT_COMPAT),
                html_entity_decode(strip_tags(htmlspecialchars_decode($this->data['body'], ENT_QUOTES | ENT_COMPAT))),
                htmlspecialchars_decode($this->data['statusname'], ENT_QUOTES | ENT_COMPAT),
                $this->data['elabid'],
                $this->url
            );
        } else {
            $this->list[] = array(
                htmlspecialchars_decode($this->data['title'], ENT_QUOTES | ENT_COMPAT),
                html_entity_decode(strip_tags(htmlspecialchars_decode($this->data['body'], ENT_QUOTES | ENT_COMPAT))),
                $this->data['id'],
                $this->data['date'],
                htmlspecialchars_decode($this->data['typename'], ENT_QUOTES | ENT_COMPAT),
                $this->data['rating'],
                $this->url
            );
        }
    }

    /**
     * Write our file
     */
    private function writeCsv()
    {
        $fp = fopen($this->filePath, 'w+');
        // utf8 headers
        fwrite($fp, "\xEF\xBB\xBF");
        foreach ($this->list as $fields) {
                fputcsv($fp, $fields);
        }
        fclose($fp);
    }
}
