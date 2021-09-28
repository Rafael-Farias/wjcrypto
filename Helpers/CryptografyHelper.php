<?php

namespace WjCrypto\Helpers;

trait CryptografyHelper
{

    private string $key = 'Qyk3y_sDb%W+?Pbu#V?uu%d8H5Wj?fA=@YyXY7jE+_X^CFzx9KVbmXYj^uP#%gV+';
    private string $cipher = "aes-256-gcm";
    private string $iv = '3EYeuc9qoUZBgXUy';

    /**
     * @param string $dataToEncrypt
     * @return false|string
     */
    public function encrypt(string $dataToEncrypt): bool|string
    {
        $iv = base64_decode('3EYeuc9qoUZBgXUy');
        $tag = null;
        if (in_array($this->cipher, openssl_get_cipher_methods())) {
            $encryptedData = openssl_encrypt($dataToEncrypt, $this->cipher, $this->key, $options = 0, $iv, $tag);
            $base64Tag = base64_encode($tag);
            return $base64Tag . $encryptedData;
        }
        return false;
    }

    /**
     * @param string $dataToDecrypt
     * @return false|string
     */
    public function decrypt(string $dataToDecrypt): bool|string
    {
        $iv = base64_decode('3EYeuc9qoUZBgXUy');
        $base64Tag = $this->getBase64TagFromEncryptedData($dataToDecrypt);
        $tag = base64_decode($base64Tag);
        $sanitizedEncryptedData = $this->getEncryptedData($dataToDecrypt);
        if (in_array($this->cipher, openssl_get_cipher_methods())) {
            return openssl_decrypt($sanitizedEncryptedData, $this->cipher, $this->key, $options = 0, $iv, $tag);
        }
        return false;
    }

    /**
     * @param string $encryptedDataWithTag
     * @return string
     */
    private function getBase64TagFromEncryptedData(string $encryptedDataWithTag): string
    {
        return substr($encryptedDataWithTag, 0, 24);
    }

    /**
     * @param string $encryptedDataWithTag
     * @return string
     */
    private function getEncryptedData(string $encryptedDataWithTag): string
    {
        return substr($encryptedDataWithTag, 24);
    }
}
