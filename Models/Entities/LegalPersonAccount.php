<?php

namespace WjCrypto\Models\Entities;

use Money\Currency;
use Money\Money;
use WjCrypto\Helpers\CryptografyHelper;

class LegalPersonAccount
{
    use CryptografyHelper;

    private int $id;
    private string $name;
    private string $cnpj;
    private string $company_register;
    private string $foundation_date;
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
        return $this->company_register;
    }

    /**
     * @param string $company_register
     */
    public function setCompanyRegister(string $company_register): void
    {
        $this->company_register = $company_register;
    }

    /**
     * @return string
     */
    public function getFoundationDate(): string
    {
        return $this->foundation_date;
    }

    /**
     * @param string $foundation_date
     */
    public function setFoundationDate(string $foundation_date): void
    {
        $this->foundation_date = $foundation_date;
    }

    /**
     * @return Money
     */
    public function getBalance(): Money
    {
        return $this->balance;
    }

    /**
     * @param $balance
     */
    public function setBalance($balance): void
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
            'cnpj' => $this->cnpj,
            'company_register' => $this->company_register,
            'foundation_date' => $this->foundation_date,
            'balance' => number_format((float)$stringBalance, 2, ',', '.'),
            'address' => $this->address->getAddress(),
            'city' => $this->city->getName(),
            'state' => $this->state->getName(),
            'accountNumber' => $this->encrypt($this->accountNumber->getAccountNumber())
        ];
    }
}
