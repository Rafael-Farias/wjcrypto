<?php

namespace WjCrypto\Models\Entities;

class ClientContact
{
    private int $id;
    private int $legalPersonAccountId;
    private int $naturalPersonAccountId;
    private string $telephone;
    private string $creation_timestamp;
    private string $update_timestamp;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getLegalPersonAccountId(): int
    {
        return $this->legalPersonAccountId;
    }

    /**
     * @param int $legalPersonAccountId
     */
    public function setLegalPersonAccountId(int $legalPersonAccountId): void
    {
        $this->legalPersonAccountId = $legalPersonAccountId;
    }

    /**
     * @return int
     */
    public function getNaturalPersonAccountId(): int
    {
        return $this->naturalPersonAccountId;
    }

    /**
     * @param int $naturalPersonAccountId
     */
    public function setNaturalPersonAccountId(int $naturalPersonAccountId): void
    {
        $this->naturalPersonAccountId = $naturalPersonAccountId;
    }

    /**
     * @return string
     */
    public function getTelephone(): string
    {
        return $this->telephone;
    }

    /**
     * @param string $telephone
     */
    public function setTelephone(string $telephone): void
    {
        $this->telephone = $telephone;
    }

    /**
     * @return string
     */
    public function getCreationTimestamp(): string
    {
        return $this->creation_timestamp;
    }

    /**
     * @param string $creation_timestamp
     */
    public function setCreationTimestamp(string $creation_timestamp): void
    {
        $this->creation_timestamp = $creation_timestamp;
    }

    /**
     * @return string
     */
    public function getUpdateTimestamp(): string
    {
        return $this->update_timestamp;
    }

    /**
     * @param string $update_timestamp
     */
    public function setUpdateTimestamp(string $update_timestamp): void
    {
        $this->update_timestamp = $update_timestamp;
    }
}