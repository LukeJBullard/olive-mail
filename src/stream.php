<?php
    /**
     * OOP IMAP Stream Structure for OliveWeb Framework Mail Module
     * 
     * @author Luke Bullard
     */

    class MailStream
    {
        protected $m_underlyingStream;
        protected $m_isClosed;

        protected $m_hostname;
        protected $m_username;
        protected $m_password;
        protected $m_port;
        protected $m_mailbox;

        private static $m_defaultPorts;

        /**
         * Constructor for MailStream
         * 
         * @param String $a_hostName The Hostname of the mail server to connect to.
         * @param Int $a_port The port of the mail server to connect to.
         * @param String $a_type The type of mail server to connect to (ie. imap, imaps, pop3, pop3s, ...)
         * @param String $a_mailbox The mailbox to open on the server.
         * @param String $a_username The username to authenticate to the mail server with.
         * @param String $a_password The password to authenticate to the mail server with.
         * 
         * @throws InvalidArgumentException When invalid arguments are passed to the constructor.
         */
        public function __construct($a_hostName, $a_port, $a_type, $a_mailbox, $a_username, $a_password)
        {
            $possibleTypes = array("imap", "imaps", "pop3", "pop3s");

            //verify argument data types
            if (!(is_string($a_hostName) && is_int($a_port) && is_string($a_type) && is_string($a_username)
                && is_string($a_mailbox) && is_string($a_password)))
            {
                throw new InvalidArgumentException("One or more arguments are of an invalid data type.");
            }

            $a_type = strtolower($a_type);

            //verify argument contents
            if ($a_hostName == "" || $a_port < 1 || !in_array($a_type, $possibleTypes) || $a_username == "" || $a_mailbox == "")
            {
                throw new InvalidArgumentException("One or more arguments have invalid contents.");
            }


        }

        /**
         * Sets up default port<->protocol mappings if they aren't already setup.
         */
        private static function setDefaultPorts()
        {
            //set the defaults if not set already
            if (!isset(MailStream::$m_defaultPorts) || !is_array(MailStream::$m_defaultPorts))
            {
                MailStream::$m_defaultPorts = array(
                    "imap" => 143,
                    "pop3" => 110,
                    "imaps" => 993,
                    "pop3s" => 995
                );
            }
        }

        /**
         * Establishes a connection to the mail server.
         * 
         * @return Boolean If the connection was successfully established.
         */
        public function connect()
        {

        }

        /**
         * Closes the connection to the mail server.
         * 
         * @return Boolean If the connection was successfully closed.
         */
        public function close()
        {

        }

        /**
         * Returns if the connection to the mail server is still established.
         * 
         * @return Boolean If the connection is still established.
         */
        public function isConnected()
        {

        }

        /**
         * Retrieves the default port number or protocol name.
         * 
         * @param Int|String $a_nameNumber The port number or name of the protocol to get the default for.
         * @return Int|String|Boolean The port number associated with the protocol passed in $a_nameNumber (if a string was passed),
         *  or the protocol name associated with the port number passed in $a_nameNumber (if an int was passed). Returns Boolean false if nothing was found.
         */
        public static function getDefaultPort($a_nameNumber)
        {
            //set default ports if they aren't already set
            MailStream::setDefaultPorts();

            //get the port number from protocol name
            if (is_string($a_nameNumber) && isset(MailStream::$m_defaultPorts[$a_nameNumber]))
            {
                return MailStream::$m_defaultPorts[$a_nameNumber];
            }

            //get the protocol name from the port number
            if (is_integer($a_nameNumber))
            {
                $key = array_search($a_nameNumber, MailStream::$m_defaultPorts);
                if (isset($key) && $key != "")
                {
                    return $key;
                }
            }

            //default, return false
            return false;
        }
    }
?>