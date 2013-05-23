<?php

/**
 * Class Contextly_Crypt
 * @author Meshin Dmitry <0x7ffec at gmail.com>
 */
class Contextly_Crypt {

    /**
     * @return Contextly_Crypt
     */
    public static function getInstance() {
        static $i = null;

        if ( null === $i ) {
            $i = new self;
        }

        return $i;
    }

    /**
     * @param $data
     * @param null $key
     * @param string $algorithm
     * @return null|string
     */
    public function encrypt( $data, $key = null, $algorithm = MCRYPT_BLOWFISH ) {
        /* Open module, and create IV */
        $td = mcrypt_module_open($algorithm, '', 'ecb', '');
        $key = substr($key, 0, mcrypt_enc_get_key_size($td));
        $iv_size = mcrypt_enc_get_iv_size($td);
        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);

        $c_t = null;

        /* Initialize encryption handle */
        if (mcrypt_generic_init($td, $key, $iv) != -1) {

            /* Encrypt data */
            $c_t = mcrypt_generic($td, $data);

            /* Clean up */
            mcrypt_generic_deinit($td);
            mcrypt_module_close($td);
        }

        return $c_t;
    }

    /**
     * @param $data
     * @param null $key
     * @param string $algorithm
     * @return null|string
     */
    public function decrypt( $data, $key = null, $algorithm = MCRYPT_BLOWFISH ) {

        if ( !$data ) {
            return null;
        }

        /* Open module, and create IV */
        $td = mcrypt_module_open($algorithm, '', 'ecb', '');
        $key = substr($key, 0, mcrypt_enc_get_key_size($td));
        $iv_size = mcrypt_enc_get_iv_size($td);
        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);

        $p_t = null;

        /* Initialize encryption handle */
        if (mcrypt_generic_init($td, $key, $iv) != -1) {

            $p_t = mdecrypt_generic($td, $data);

            /* Clean up */
            mcrypt_generic_deinit($td);
            mcrypt_module_close($td);
        }

        return $p_t;
    }

}