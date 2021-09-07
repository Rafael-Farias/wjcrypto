<?php

namespace WjCrypto\Models\Entities;

class ClientContact
{
    private int $id;
    private int $legal_person_account_id;
    private int $natural_person_account_id;
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
        return $this->legal_person_account_id;
    }

    /**
     * @param int $legal_person_account_id
     */
    public function setLegalPersonAccountId(int $legal_person_account_id): void
    {
        $this->legal_person_account_id = $legal_person_account_id;
    }

    /**
     * @return int
     */
    public function getNaturalPersonAccountId(): int
    {
        return $this->natural_person_account_id;
    }

    /**
     * @param int $natural_person_account_id
     */
    public function setNaturalPersonAccountId(int $natural_person_account_id): void
    {
        $this->natural_person_account_id = $natural_person_account_id;
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