<?php
/**
 * @author Ondřej Doněk, <ondrejd@gmail.com>
 * @package sbazar_crawler
 */

namespace sbazar_crawler;

/**
 * Pomocný objekt pro zachytávání chyb při parsování HTML.
 * @link https://stackoverflow.com/questions/1148928/disable-warnings-when-loading-non-well-formed-html-by-domdocument-php
 */
class ParserError {
    /**
     * @var callable $callback
     */
    protected $callback;

    /**
     * @var array $errors
     */
    protected $errors;

    /**
     * Constructor.
     * @param callable $callback
     * @return void
     */
    function __construct( $callback ) {
        $this->callback = $callback;
    }

    /**
     * Call the watched callback.
     * @return void
     */
    public function call() {
        $result = null;
        set_error_handler( [$this, 'on_error'] );

        try {
            $result = call_user_func( $this->callback, func_get_arg( 0 ) );
        } catch (Exception $ex) {
            restore_error_handler();        
            //throw $ex;
        }

        restore_error_handler();
        return $result;
    }

    /**
     * Called when error is occured.
     * @param string $errno
     * @param string $errstr
     * @param string $errfile
     * @param integer $errline
     * @return void
     */
    public function on_error( $errno, $errstr, $errfile, $errline ) {
        $this->errors[] = [$errno, $errstr, $errfile, $errline];
    }

    /**
     * @return boolean Returns TRUE if there were no errors.
     */
    public function ok() {
        return count( $this->errors ) <= 0;
    }

    /**
     * @return array Array with an errors.
     */
    public function errors() {
        return $this->errors;
    }
}