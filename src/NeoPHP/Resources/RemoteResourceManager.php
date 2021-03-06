<?php

namespace NeoPHP\Resources;

use Curl\Curl;
use NeoPHP\Query\DeleteQuery;
use NeoPHP\Query\InsertQuery;
use NeoPHP\Query\Query;
use NeoPHP\Query\SelectQuery;
use NeoPHP\Query\UpdateQuery;
use RuntimeException;
use Throwable;

/**
 * Class RemoteResource
 * @package NeoPHP\Resources
 */
class RemoteResourceManager extends ResourceManager {

    private $remoteUrl;

    /**
     * Obtiene la url remota donde se sirve el recurso
     * @return string
     */
    public function getRemoteUrl(): string {
        return $this->remoteUrl;
    }

    /**
     * Establece la url remota donde se sirve el recurso
     * @param string $remoteUrl
     */
    public function setRemoteUrl(string $remoteUrl) {
        $this->remoteUrl = $remoteUrl;
    }

    /**
     * Metodo para buscar recursos
     * @param SelectQuery $query
     * @return mixed
     * @throws \ErrorException
     */
    public function find(SelectQuery $query) {
        return $this->getRemoteContents($query);
    }

    /**
     * Método para insertar un nuevo recurso
     * @param InsertQuery $query
     * @return mixed
     * @throws \ErrorException
     */
    public function insert(InsertQuery $query) {
        return $this->getRemoteContents($query);
    }

    /**
     * Método para actualizar un recurso
     * @param UpdateQuery $query
     * @return mixed
     * @throws \ErrorException
     */
    public function update(UpdateQuery $query) {
        return $this->getRemoteContents($query);
    }

    /**
     * Método para borrar un recurso
     * @param DeleteQuery $query
     * @return mixed
     * @throws \ErrorException
     */
    public function delete(DeleteQuery $query) {
        return $this->getRemoteContents($query);
    }

    /**
     * @param Query $query
     * @return null
     * @throws \ErrorException
     * @todo authentication in curl remote contents
     */
    private function getRemoteContents (Query $query) {
        $curl = new Curl();
        $curl->setHeader("Accept-Encoding", "application/gzip");
        $curl->setHeader("Content-Type", "application/sql");
        $curl->post($this->getRemoteUrl(), serialize($query), true);
        if ($curl->error) {
            $messageError = "Remote exception - " . $curl->errorMessage;
            if (isset($curl->response)) {
                try {
                    $res = json_decode($curl->response);
                    $messageError .= (": \"".$res->message."\"");
                }catch (Throwable $exception) {}
            }
            $messageError .= " in $curl->url";
            throw new RuntimeException($messageError , $curl->errorCode);
        }
        return $curl->response;
    }
}