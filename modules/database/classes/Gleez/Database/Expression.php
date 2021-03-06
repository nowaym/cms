<?php
/**
 * Gleez CMS (http://gleezcms.org)
 *
 * @link https://github.com/gleez/database Canonical source repository
 * @copyright Copyright (c) 2011-2014 Gleez Technologies
 * @license http://gleezcms.org/license Gleez CMS License
 */

namespace Gleez\Database;

/**
 * MySQLi database Expression
 *
 * Wraps expressions so they aren't quoted or modified
 * when inserted into the query
 *
 * @package Gleez\Database
 * @version 2.0
 * @author Gleez Team
 */
class Expression {
    /**
     * The expression content
     *
     * @var  string
     */
    protected $string;

    /**
     * The constructor accepts the expression as string
     *
     * @param  string  $string  The content to prevent being quoted
     */
    public function __construct($string = '')
    {
        $this->string = $string;
    }

    /**
     * Return the unmodified expression
     *
     * @return  string  The unaltered content of the expression
     */
    public function value()
    {
        return (string) $this->string;
    }

    /**
     * Returns the unmodified expression
     *
     * @return  string  The unaltered content of the expression
     */
    public function __toString()
    {
        return (string) $this->value();
    }
}
