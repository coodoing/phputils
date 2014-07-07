<?php

abstract class PU_SecurityAbstract{
	/**
     * @param string $plaintext
     * @return string $ciphertext
     */
    abstract public function encryption ($plaintext);
    /**
     * @param string $ciphertext
     * @return string $plaintext
     */
    abstract public function decryption ($ciphertext);
    /**
     * Get encryption key
     */
    abstract public function getEncryptionKey ();
    /**
     * Get decryption key
     */
    abstract public function getDecryptionKey ();
    /**
     * Get current en/decryption method(RSA/DES etc.)
     */
    abstract public function getEncryptionType ();
    /**
     * Get the error message triggered very recently
     */
    abstract public function getLastError ();
    /**
     * Get the max length of plaintext byte for each encryption
     */
    abstract public function getMaxLenCrypted ();
}