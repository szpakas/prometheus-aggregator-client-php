<?php

namespace Szpakas\PrometheusAggregator\Client;

class FlusherUDP
{
    /**
     * Max size of an UDP packet sent to server.
     * Used to combine multiple stats into one packet.
     *
     * Size in characters, should never exceed UDP packet limit (65k), but please reserve some buffer.
     * As long as we are talking over loopback, we can go quite high.
     */
    const MAX_PACKET_SIZE = 4096;

    /**
     * @var resource
     */
    private $socket;

    /**
     * @var string
     */
    private $host;

    /**
     * @var int
     */
    private $port;

    /**
     * FlusherUDP constructor.
     * @param string $host
     * @param int $port
     */
    public function __construct($host, $port)
    {
        $this->host = $host;
        $this->port = $port;
    }

    /**
     * @param Registry $r
     * @return bool False on failure
     */
    public function flush(Registry $r)
    {
        $this->socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);

        if (false === $this->socket) {
            return false;
        }

        $data = $r->collect();

        $packetPayload = $data["sharedLabels"] ?: "";
        // loop over all stats
        foreach($data["metrics"] as $msg) {
            // make sure that adding another metric does not create a packet larger than max size of packet
            // if so than send the packer
            if (strlen($packetPayload."\n".$msg) > static::MAX_PACKET_SIZE) {
                $this->sendPacket($packetPayload);
                // reset for next packet, add shared labels on top if required
                $packetPayload = $data["sharedLabels"] ?: "";
            }
            $packetPayload .= "\n".$msg;
        }

        // send last packet if necessary
        if ($packetPayload) {
            $this->sendPacket($packetPayload);
        }

        socket_close($this->socket);

        return true;
    }

    /**
     * Sends single packet with stats and handles the response.
     *
     * @param string $payload
     * TODO(szpakas): add support for send failures including buffer overflows
     */
    private function sendPacket($payload)
    {
        socket_sendto($this->socket, $payload, strlen($payload), 0, $this->host, $this->port);
    }
}
