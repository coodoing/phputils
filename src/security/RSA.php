<?php

class PU_RSA extends PU_SecurityAbstract{

    const LOCAL  = 0x01;
    const REMOTE = 0x02;
    const FILE   = 0x04;

    private $_bEncryptNormal; 
    private $_errorMsg = ''; // error msg
    private $_maxLifeCA = 366;// 365 days 
    private $_x509CAFileDir = '/tmp';
    private $_x509CAFile;
    private $_x509CAStr;
    private $_privateKeyFile = 'private.pem';

    private $_rcKeysPair;                                                                   
    private $_publicKey;
    private $_privateKey;

    private $_du = array(

    );
    private $_config = array(
                    "digest_alg" => "sha512",
                    "private_key_bits" => 4096,
                    "private_key_type" => OPENSSL_KEYTYPE_RSA,
    );

	public function __construct ($method=self::LOCAL, $x509CAFile='', $x509CAFileDir='') {
        // be sure to open the assert function
        assert(extension_loaded ('openssl')) or die ('openssl extension NOT LOADED!');
        $this->_pubKeyGenerationMethod = $method;        
        /**
        * true--encrypt with public key, decrypt with private key
        * false--encrypt with private key, decrypt with public key
        */
        $this->_bEncryptNormal = true;

        switch ($method) {
            case self::LOCAL:
                $this -> _getPrivateKeyLocal ();
                $this -> _getPublicKeyLocal ();
                break;
            case self::REMOTE:
                break;
            case self::FILE:
                //
                if ($x509CAFileDir) {
                    $this->_x509CAFileDir = $x509CAFileDir;
                } 
                if (!is_dir ($this->_x509CAFileDir)) {
                    throw new PU_Exception ('RSA X509CA file directory:' . $this->_x509CAFileDir . ' does not exist!');
                }
                
                if ($x509CAFile) {
                    $this->_x509CAFile = $x509CAFile;
                } 
                if (!$this->_x509CAFile) {
                    throw new PU_Exception ('RSA X509CA file name is empty!');
                }
                $_x509CAFile     = $this->_x509CAFileDir . DIRECTORY_SEPARATOR . $this->_x509CAFile;
                $_privateKeyFile = $this->_x509CAFileDir . DIRECTORY_SEPARATOR . $this->_privateKeyFile;
                $this -> generateCA ();
       
                $this -> _getPrivateKeyFromFile ();
                $this -> _getPublicKeyFromFile ();
                break;
            default:
                throw new PU_Exception ('RSA public/private key generation method:' . $method . ' not supported!');
                break;    
        }//switch
	}	

	public function encryption ($plaintext){
        if ($this->_bEncryptNormal) {
            return $this -> _encryptWithPublicKey($plaintext);
        } else {
            return $this -> _encryptWithPrivateKey($plaintext);
        }
	}

    public function decryption ($ciphertext){
        if ($this->_bEncryptNormal) {
            return $this -> _decryptWithPrivateKey($ciphertext);
        } else {
            return $this -> _decryptWithPublicKey($ciphertext);
        }
    }

    public function getEncryptionKey (){
        if ($this->_bEncryptNormal) {
            return $this -> getPublicKey();
        } else {
            return $this -> getPrivateKey();
        }
    }

    public function getDecryptionKey (){
        if ($this->_bEncryptNormal) {
            return $this -> getPrivateKey();
        } else {
            return $this -> getPublicKey();
        }
    }

    public function getEncryptionType (){
        return "RSA";
    }
 
    public function getLastError (){

    }

    public function getMaxLenCrypted (){

    }

    /**
     * Generate self certificated authorization stored into $this->_x509CAFile;
     * private key stored into $this->_privateKeyFile(with value of private.pem) and also the public key.
     */
    private function generateCA () {
        $this->_rcKeysPair = openssl_pkey_new ($this->_config);

        $this -> _getPrivateKeyLocal ();
        $this -> _getPublicKeyLocal ();
        $_privateKeyFile = $this->_x509CAFileDir . DIRECTORY_SEPARATOR . $this->_privateKeyFile;
        if (file_exists ($_privateKeyFile)) {
            $_ok = rename ($_privateKeyFile, $_privateKeyFile . '-' . date('Y-m-d_H:i:s'));
            if (!$_ok) {
                $this->_errorMsg = sprintf ('RSA:generateCA(), cannot rename private key file(%s).', $_privateKeyFile);
                new PU_LOG()->logError($this->_errorMsg);
            }
        }
        $_ok = file_put_contents ($_privateKeyFile, $this->_privateKey);
        if (!$_ok) {
            $this->_errorMsg = sprintf ('RSA:generateCA(), cannot write private key(%s) into file(%s).', $this->_privateKey, $_privateKeyFile);
            new PU_LOG()->logError($this->_errorMsg);
        }
        
        //generate self certificated authorization(x509)
        $_csr   = openssl_csr_new ($this->_dn, $this->_rcKeysPair);
        $_sscert= openssl_csr_sign ($_csr, NULL, $this->_rcKeysPair, $this->_maxLifeCA);

        openssl_x509_export ($_sscert, &$_x509CAContent);
        $this->_x509CAStr = $_x509CAContent;
        $_x509CAFile = $this->_x509CAFileDir . DIRECTORY_SEPARATOR . $this->_x509CAFile;
        if (file_exists ($_x509CAFile)) {
            $_ok = rename ($_x509CAFile, $_x509CAFile . '-' . date('Y-m-d_H:i:s'));
            if (!$_ok) {
                $this->_errorMsg = sprintf ('RSA:generateCA(), cannot rename x509CA file(%s).', $_x509CAFile);
                new PU_LOG()->logError($this->_errorMsg);
            }
            file_put_contents ($x509CAContent);
        }
        $_ok = file_put_contents ($_x509CAFile, $_x509CAContent);
        if (!$_ok) {
            $this->_errorMsg = sprintf ('RSA:generateCA(), cannot write CA(%s) into x509CA file(%s).', $_x509CAContent, $_x509CAFile);
            new PU_LOG()->logError ($this->_errorMsg);
        }
    }

    public function getPublicKey () {
        return $this->_publicKey;
    }

    public function getPrivateKey () {
        return $this->_privateKey;
    }


    private function _getPrivateKeyLocal () {
        if (!is_resource ($this->_rcKeysPair)) {
            $this->_rcKeysPair = openssl_pkey_new ($this->_config);
        }
        if (openssl_pkey_export ($this->_rcKeysPair, &$this->_privateKey)) {
            $this->_errorMsg = '';
            return $this->_privateKey;
        } else {
            $this->_errorMsg = openssl_error_string ();
            new PU_LOG()->logError (sprintf ('RSA:_initPrivateKeyLocal(), error message(%s)', $this->_errorMsg));
            $this->_privateKey = false;
            return false;   
        }
    }

    private function _getPublicKeyLocal () {
        if (!is_resource ($this->_rcKeysPair)) {
            $this->_rcKeysPair = openssl_pkey_new ($this->_config);
        }
        $_details = openssl_pkey_get_details ($this->_rcKeysPair);
        if (is_array ($_details) && $_details['key']) {
            $this->_publicKey = $_details['key'];
            $this->_errorMsg  = '';
            return $this->_publicKey;
        } else {
            $this->_errorMsg = openssl_error_string ();
            new PU_LOG()->logError (sprintf ('RSA:_initPublicKeyLocal(), error message(%s)', $this->_errorMsg));
            $this->_publicKey = false;
            return false;
        }
    }

    private function _getPrivateKeyFromFile () {
        $_privateKeyFile = $this->_x509CAFileDir . DIRECTORY_SEPARATOR . $this->_privateKeyFile;
        if (file_exists ($_privateKeyFile) && is_readable ($_privateKeyFile)) {
            $this->_privateKey = file_get_contents ($_privateKeyFile);
            $this->_errorMsg   = '';
            return $this->_privateKey;
        } else {
            $this->_errorMsg = sprintf ('RSA:_getPrivateKeyFromFile(), private key file(%s) not exists or unreadable.', $_privateKeyFile);
            new PU_LOG()->logError ($this->_errorMsg);
            $this->_privateKey = false;
            return false;
        }
    }

    private function _getPublicKeyFromFile () {
        if (!$this->_x509CAStr) {
            $_CAFilePath = $this->_x509CAFileDir . DIRECTORY_SEPARATOR . $this->_x509CAFile;
            if (file_exists ($_CAFilePath) && is_readable($_CAFilePath)) {
                $this->_x509CAStr = file_get_contents ($_CAFilePath);
                $this->_errorMsg  = '';
            } else {
                $this->_errorMsg = sprintf ('RSA:_getPublicKeyFromFile(), CA file(%s) not exists or unreadable.', $_CAFilePath);
                new PU_LOG()->logError ($this->_errorMsg);
            }
        }

        $_rcPublicKey = openssl_pkey_get_public ($this->_x509CAStr);
        if (is_resource ($_rcPublicKey)) {
            $_detail = openssl_pkey_get_details ($_rcPublicKey);
            if (is_array ($_detail) && $_detail['key']) {
                $this->_publicKey = $_detail['key'];
                $this->_errorMsg  = '';
                return $this->_publicKey;
            } else {
                $this->_errorMsg = openssl_error_string ();
                new PU_LOG()->logError (sprintf ('RSA:_getPublicKeyFromFile(), detail(%s), error message(%s).', json_encode($_detail), $this->_errorMsg));
                $this->_publicKey = false;
                return false;
            }
        } else {
            $this->_errorMsg = openssl_error_string ();
            new PU_LOG()->logError (sprintf ('RSA:_getPublicKeyFromFile(), error message(%s).', $this->_errorMsg));
            $this->_publicKey = false;
            return false;            
        }
    }

    private function _encryptWithPublicKey ($plaintext) {
        assert (strlen ($this->_publicKey));
        $_ciphertext = ''; 
        $_ok = openssl_public_encrypt ($plaintext, &$_ciphertext, $this->_publicKey);
        if ($_ok) {
            $this->_errorMsg = '';
            return $_ciphertext;
        } else {
            $this->_errorMsg = openssl_error_string ();
            new PU_LOG()->logError (sprintf ('RSA:_encryptWithPublicKey(%s), publicKey(%s), error message(%s)', $plaintext, $this->_publicKey, $this->_errorMsg));
            return false;
        }
    }

    private function _decryptWithPrivateKey ($ciphertext) {
        assert (strlen ($this->_privateKey));
        $_plaintext = '';
        $_ok = openssl_private_decrypt ($ciphertext, &$_plaintext, $this->_privateKey);
        if ($_ok) {
            $this->_errorMsg = '';
            return $_plaintext;
        } else {
            $this->_errorMsg = openssl_error_string ();
            new PU_LOG()->logError (sprintf ('RSA:_decryptWithPrivateKey(%s), privateKey(%s), error message(%s)', $ciphertext, $this->_privateKey, $this->_errorMsg));
            return false;            
        }
    }

    private function _encryptWithPrivateKey ($plaintext) {
        assert (strlen ($this->_privateKey));
        $_ciphertext = ''; 
        $_ok = openssl_private_encrypt ($plaintext, &$_ciphertext, $this->_privateKey);
        if ($_ok) {
            $this->_errorMsg = '';
            return $_ciphertext;
        } else {
            $this->_errorMsg = openssl_error_string ();
            new PU_LOG()->logError (sprintf ('RSA:_encryptWithPrivateKey(%s), privateKey(%s), error message(%s)', $plaintext, $this->_privateKey, $this->_errorMsg));
            return false;
        }        
    }

    private function _decryptWithPublicKey ($ciphertext) {
        assert (strlen ($this->_publicKey));
        $_plaintext = '';
        $_ok = openssl_public_decrypt ($ciphertext, &$_plaintext, $this->_publicKey);
        if ($_ok) {
            $this->_errorMsg = '';
            return $_plaintext;
        } else {
            $this->_errorMsg = openssl_error_string ();
            new PU_LOG()->logError (sprintf ('RSA:_decryptWithPublicKey(%s), publicKey(%s), error message(%s)', $ciphertext, $this->_publicKey, $this->_errorMsg));
            return false;            
        }
    }
}