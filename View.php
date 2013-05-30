<?php
/**
 * @author: Dmitriy Meshin <0x7ffec at gmail.com>
 */
class Socialer_View {

    /**
     * @var array
     */
    protected $vars = array();

    /**
     * @param string $name
     * @param mixed $value
     */
    public function assign( $name, $value ) {
        $name = (String)$name;
        $this->vars[$name] = $value;
    }

    /**
     * @param $template_name
     * @return string
     */
    public function render( $template_name ) {
        ob_start();
        require $template_name;
        $result = ob_get_clean();
        return $result;
    }

    /**
     * @param $name
     * @return null
     */
    public function __get( $name ) {
        if ( isset( $this->vars[$name] ) ) {
            return $this->vars[$name];
        }

        return null;
    }

}