<?php

namespace WjCrypto\Models\Entities;

use Money\Currency;
use Money\Money;

class NaturalPersonAccount
{
    private int $id;
    private string $name;
    private string $cpf;
    private string $rg;
    private string $birth_date;
    private Money $balance;
    private int $address_id;
    private Address $address;
    private City $city;
    private State $state;
    private AccountNumber $accountNumber;
    private string $creation_timestamp;
    private string $update_timestamp;

    /**
     * @param Address $address
     * @param City $city
     * @param State $state
     * @param AccountNumber $accountNumber
     */
    public function __construct(Address $address, City $city, State $state, AccountNumber $accountNumber)
    {
        $this->address = $address;
        $this->city = $city;
        $this->state = $state;
        $this->accountNumber = $accountNumber;
    }


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
        return $this->birth_date;
    }

    /**
     * @param string $birth_date
     */
    public function setBirthDate(string $birth_date): void
    {
        $this->birth_date = $birth_date;
    }

    /**
     * @return Money
     */
    public function getBalance(): Money
    {
        return $this->balance;
    }

    /**
     * @param string $balance
     */
    public function setBalance(string $balance): void
    {
        $newBalance = new Money($balance, new Currency('BRL'));
        $this->balance = $newBalance;
    }

    /**
     * @return int
     */
    public function getAddressId(): int
    {
        return $this->address_id;
    }

    /**
     * @param int $address_id
     */
    public function setAddressId(int $address_id): void
    {
        $this->address_id = $address_id;
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

    /**
     * @return Address
     */
    public function getAddress(): Address
    {
        return $this->address;
    }

    /**
     * @param Address $address
     */
    public function setAddress(Address $address): void
    {
        $this->address = $address;
    }

    /**
     * @return City
     */
    public function getCity(): City
    {
        return $this->city;
    }

    /**
     * @param City $city
     */
    public function setCity(City $city): void
    {
        $this->city = $city;
    }

    /**
     * @return State
     */
    public function getState(): State
    {
        return $this->state;
    }

    /**
     * @param State $state
     */
    public function setState(State $state): void
    {
        $this->state = $state;
    }

    /**
     * @return AccountNumber
     */
    public function getAccountNumber(): AccountNumber
    {
        return $this->accountNumber;
    }

    /**
     * @param AccountNumber $accountNumber
     */
    public function setAccountNumber(AccountNumber $accountNumber): void
    {
        $this->accountNumber = $accountNumber;
    }

    /**
     * @return array
     */
    public function getAccountData(): array
    {
        $balance = $this->balance->getAmount();
        $decimal = substr($balance, -2);
        $integer = substr($balance, 0, -2);
        $stringBalance = $integer . '.' . $decimal;

        return [
            'name' => $this->name,
            'cpf' => $this->cpf,
            'rg' => $this->rg,
            'birth_date' => $this->birth_date,
            'balance' => number_format((float)$stringBalance, 2, ',', '.'),
            'address' => $this->address->getAddress(),
            'city' => $this->city->getName(),
            'state' => $this->state->getName(),
            'accountNumber' => $this->accountNumber->getAccountNumber()
        ];
    }
}