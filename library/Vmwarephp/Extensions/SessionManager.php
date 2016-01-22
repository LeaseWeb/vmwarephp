<?php
namespace Vmwarephp\Extensions;

use \Vmwarephp\ManagedObject;

/**
 * Class SessionManager
 * @package Vmwarephp\Extensions
 */
class SessionManager extends ManagedObject
{

    private $cloneTicketFile;
    private $session;

    /**
     * @param $userName
     * @param $password
     *
     * @return mixed
     */
    public function acquireSession($userName, $password)
    {
        if ($this->session) {
            return $this->session;
        }
        try {
            $this->session = $this->acquireSessionUsingCloneTicket();
        } catch (\Exception $e) {
            $this->session = $this->acquireANewSession($userName, $password);
        }
        return $this->session;
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    private function acquireSessionUsingCloneTicket()
    {
        $cloneTicket = $this->readCloneTicket();
        if (!$cloneTicket) {
            throw new \Exception('Cannot find any clone ticket.');
        }
        return $this->CloneSession(['cloneTicket' => $cloneTicket]);
    }

    /**
     * @param $userName
     * @param $password
     *
     * @return mixed
     * @throws \Exception
     */
    private function acquireANewSession($userName, $password)
    {
        $session = $this->Login(['userName' => $userName, 'password' => $password, 'locale' => null]);
        $cloneTicket = $this->AcquireCloneTicket();
        $this->saveCloneTicket($cloneTicket);
        return $session;
    }

    /**
     * @param $cloneTicket
     *
     * @throws \Exception
     */
    private function saveCloneTicket($cloneTicket)
    {
        if (!file_put_contents($this->getCloneTicketFile(), $cloneTicket)) {
            $exceptionMessage = sprintf(
                'There was an error writing to the clone ticket path. Check the permissions of the cache directory(%s)',
                __DIR__ . '/../'
            );
            throw new \Exception(
                $exceptionMessage
            );
        }
    }

    /**
     * @return string
     */
    private function readCloneTicket()
    {
        $ticketFile = $this->getCloneTicketFile();
        if (file_exists($ticketFile)) {
            return file_get_contents($ticketFile);
        }
    }

    /**
     * @return string
     */
    private function getCloneTicketFile()
    {
        if (!$this->cloneTicketFile) {
            $this->cloneTicketFile = __DIR__ . '/../.clone_ticket.cache';
        }
        return $this->cloneTicketFile;
    }
}
