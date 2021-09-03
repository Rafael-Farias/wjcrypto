<?php

namespace WjCrypto\Models\Entities;

class LegalPersonAccount
{
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
    public function getCnpj(): string
    {
        return $this->cnpj;
    }

    /**
     * @param string $cnpj
     */
    public function setCnpj(string $cnpj): void
    {
        $this->cnpj = $cnpj;
    }

    /**
     * @return string
     */
    public function getCompanyRegister(): string
    {
        return $this->companyRegister;
    }

    /**
     * @param string $companyRegister
     */
    public function setCompanyRegister(string $companyRegister): void
    {
        $this->companyRegister = $companyRegister;
    }

    /**
     * @return string
     */
    public function getFoundationDate(): string
    {
        return $this->foundationDate;
    }

    /**
     * @param string $foundationDate
     */
    public function setFoundationDate(string $foundationDate): void
    {
        $this->foundationDate = $foundationDate;
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

    private int $id;
    private string $name;
    private string $cnpj;
    private string $companyRegister;
    private string $foundationDate;
    private float $balance;
    private int $addressId;
    private string $creation_timestamp;
    private string $update_timestamp;
}