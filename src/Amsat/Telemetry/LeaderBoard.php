<?php

namespace Amsat\Telemetry;

use Amsat\FoxDb;

class LeaderBoard {

    private $conn;

    public function __construct(FoxDb\ConnectionManager $conn_mgr, $dev_mode=false) {
        $this->conn = $conn_mgr->getPdoConnection();
        $this->dev_mode = $dev_mode;
        $this->max_dt = null;
    }

    public function getDefaultEndDateTime() {
        if($this->max_dt !== null) {
            return $this->max_dt;
        }

        $this->max_dt = $this->dev_mode ? $this->getMaxDateTime() : new \DateTime();

        return $this->max_dt;
    }

    public function getMaxDateTime() {
        //return new \DateTime($this->conn->query("SELECT MAX(date_time) AS max_dt FROM STP_HEADER")->fetch()['max_dt'], new \DateTimeZone("UTC"));
        return new \DateTime($this->conn->query("SELECT MAX(date_time) AS max_dt FROM STP_HEADER")->fetch()['max_dt']);
    }

    public function getTotalFrameCountBySpacecraft($spacecraft) {
        $spacecraft_id = (int)$spacecraft;

        $stmt = "SELECT COUNT(*) AS sumCountHeader from STP_HEADER WHERE id=:id";
        $stmt->bindParam(":id", $spacecraft_id);
        $stmt->execute();

        $header_count = $stmt->fetch(\PDO::FETCH_ASSOC)['sumCountHeader'];

        $stmt = "SELECT total AS sumCountArchive FROM STP_ARCHIVE_TOTALS WHERE id=:id";
        $stmt->bindParam(":id", $spacecraft_id);
        $stmt->execute();

        $archive_cout = $stmt->fetch(\PDO::FETCH_ASSOC)['sumCountArchive'];

        return $header_count + $archive_count;
    }

    public function passMapSearch($spacecraft, \DateTime $start, \DateTime $end=null) {
        if($end == null) {
            $end = $this->getDefaultEndDateTime();
        }

        $spacecraft_id = (int)$spacecraft;

        $query =  "SELECT receiver, rx_location, COUNT(*) AS frame_count FROM STP_HEADER ";
        $query .= "WHERE id=:id AND date_time >= :start AND date_time < :end ";
        $query .= "GROUP BY receiver, rx_location";

        $stmt = $this->conn->prepare($query);

        $start_str = $start->format("Y-m-d H:i:s");
        $end_str   = $end->format("Y-m-d H:i:s");

        $stmt->bindParam(":id", $spacecraft_id);
        $stmt->bindParam(":start", $start_str);
        $stmt->bindParam(":end", $end_str);
        $stmt->execute();

        $output = [];
        foreach($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $o_row = [];
            $o_row['receiver']    = $row['receiver'];
            $o_row['frame_count'] = $row['frame_count'];
            $o_row['rx_location'] = $this->convertRxLocation($row['rx_location'], true);

            $output[] = $o_row;
        }

        return $output;
    }

    // Flip Param Exists because OpenLayers takes a Lon/Lat combo
    public function convertRxLocation($str_rx_location, $flip=false) {
        $l_parts = explode(' ', $str_rx_location);

        $lat_m = $l_parts[0] == 'N' ? 1 : -1;
        $lat   = (float)$l_parts[1] * $lat_m;
        $lon_m = $l_parts[2] == 'E' ? 1 : -1;
        $lon   = (float)$l_parts[3] * $lon_m;

        return !$flip ? [$lat, $lon] : [$lon, $lat];
    }

}
