<?php

namespace WjCrypto\Models\Entities;

class AccountNumber
{
    private int $id;
    private int $user_id;
    private string $account_number;
    private $natural_person_account_id;
    private $legal_person_account_id;
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
    public function getUserId(): int
    {
        return $this->user_id;
    }

    /**
     * @param int $user_id
     */
    public function setUserId(int $user_id): void
    {
        $this->user_id = $user_id;
    }

    /**
     * @return string
     */
    public function getAccountNumber(): string
    {
        return $this->account_number;
    }

    /**
     * @param string $account_number
     */
    public function setAccountNumber(string $account_number): void
    {
        $this->account_number = $account_number;
    }

    /**
     * @return mixed
     */
    public function getNaturalPersonAccountId()
    {
        return $this->natural_person_account_id;
    }

    /**
     * @param mixed $natural_person_account_id
     */
    public function setNaturalPersonAccountId($natural_person_account_id): void
    {
        $this->natural_person_account_id = $natural_person_account_id;
    }

    /**
     * @return mixed
     */
    public function getLegalPersonAccountId()
    {
        return $this->legal_person_account_id;
    }

    /**
     * @param mixed $legal_person_account_id
     */
    public function setLegalPersonAccountId($legal_person_account_id): void
    {
        $this->legal_person_account_id = $legal_person_account_id;
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