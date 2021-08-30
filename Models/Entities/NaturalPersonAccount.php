<?php

namespace WjCrypto\Models\Entities;

class NaturalPersonAccount
{
    private int $id;
    private string $name;
    private string $cpf;
    private string $rg;
    private string $birthDate;
    private float $balance;
    private int $addressId;
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
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getCpf(): string
    {
        return $this->cpf;
    }

    /**
     * @param string $cpf
     */
    public function setCpf(string $cpf): void
    {
        $this->cpf = $cpf;
    }

    /**
     * @return string
     */
    public function getRg(): string
    {
        return $this->rg;
    }

    /**
     * @param string $rg
     */
    public function setRg(string $rg): void
    {
        $this->rg = $rg;
    }

    /**
     * @return string
     */
    public function getBirthDate(): string
    {
        return $this->birthDate;
    }

    /**
     * @param string $birthDate
     */
    public function setBirthDate(string $birthDate): void
    {
        $this->birthDate = $birthDate;
    }

    /**
     * @return float
     */
    public function getBalance(): float
    {
        return $this->balance;
    }

    /**
     * @param float $balance
     */
    public function setBalance(float $balance): void
    {
        $this->balance = $balance;
    }

    /**
     * @return int
     */
    public function getAddressId(): int
    {
        return $this->addressId;
    }

    /**
     * @param int $addressId
     */
    public function setAddressId(int $addressId): void
    {
        $this->addressId = $addressId;
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