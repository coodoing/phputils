<?php

class Des{

	private $key;
	private $iv;
	private $td;
	public function __construct($key,$iv = 0){
        if(!empty($key)) {
            $this->key = $key;       
            $this->td = mcrypt_module_open('des', '', 'ecb', '');      
    		$this->iv = mcrypt_create_iv (mcrypt_enc_get_iv_size($this->td), MCRYPT_RAND); 
        }
    }

    private function pkcs5_pad($text, $blocksize){
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }

    private function pkcs5_unpad($text){
        $pad = ord($text{strlen($text)-1});
        if ($pad > strlen($text)){
            return false;
        }
        if( strspn($text, chr($pad), strlen($text) - $pad) != $pad){
            return false;
        }
        return substr($text, 0, -1 * $pad);
    }

    public function encrypt($text){        
    	$this->td = mcrypt_module_open('des', '', 'ecb', ''); 
        mcrypt_generic_init($this->td, $this->key, $this->iv);
        $size = mcrypt_get_block_size('des', 'ecb'); 
    	$text = $this->pkcs5_pad($text, $size); 
        $encryptData = base64_encode(mcrypt_generic($this->td,$text));
        mcrypt_generic_deinit($this->td);
        mcrypt_module_close($this->td);
        return $encryptData;
    }

    public function decrypt($data){
    	$this->td = mcrypt_module_open('des', '', 'ecb', ''); 
        mcrypt_generic_init($this->td, $this->key, $this->iv);
        $decryptText  = $this->pkcs5_unpad(mdecrypt_generic($this->td, base64_decode($data)));
        mcrypt_generic_deinit($this->td);
        mcrypt_module_close($this->td);
        return $decryptText;
    }
}

?>