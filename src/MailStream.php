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
        protected $m_keepAlive;

        protected $m_hostname;
        protected $m_username;
        protected $m_password;
        protected $m_port;
        protected $m_mailbox;
        protected $m_type;

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
         * @param Boolean $a_keepAlive If the stream should automatically reconnect and ping the server to login.
         * 
         * @throws InvalidArgumentException When invalid arguments are passed to the constructor.
         */
        public function __construct($a_hostName, $a_port, $a_type, $a_mailbox, $a_username, $a_password, $a_keepAlive = true)
        {
            $possibleTypes = array("imap", "imaps", "pop3", "pop3s");

            //verify argument data types
            if (!(is_string($a_hostName) && is_int($a_port) && is_string($a_type) && is_string($a_username)
                && is_string($a_mailbox) && is_string($a_password) && is_bool($a_keepAlive)))
            {
                throw new InvalidArgumentException("One or more arguments are of an invalid data type.");
            }

            $this->m_type = strtolower($a_type);

            //verify argument contents
            if ($a_hostName == "" || $a_port < 1 || !in_array($a_type, $possibleTypes) || $a_username == "" || $a_mailbox == "")
            {
                throw new InvalidArgumentException("One or more arguments have invalid contents.");
            }

            $this->m_keepAlive = $a_keepAlive;
            $this->m_hostname = $a_hostName;
            $this->m_port = $a_port;
            $this->m_mailbox = $a_mailbox;
            $this->m_username = $a_username;
            $this->m_password = $a_password;

            //connect to the server
            $this->m_isClosed = true;
            if (!$this->connect())
            {
                return;
            }
        }

        /**
         * Marks an email to be deleted from the server.
         * 
         * @param MailEmail $a_mailEmail The email to be deleted.
         * @param Boolean $a_expunge If the emails marked for deletion should be deleted in this command
         *  rather than waiting for the expunge to happen normally.
         * @return Boolean If the email was successfully marked to be deleted from the server.
         */
        public function deleteEmail($a_mailEmail, $a_expunge = false)
        {
            imap_delete($this->m_underlyingStream, $a_mailEmail->getMessageNumber());

            if ($a_expunge)
            {
                imap_expunge($this->m_underlyingStream);
            }
            return true;
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
         * @throws Exception If the protocol type is invalid.
         */
        public function connect()
        {
            //if supposedly connected, verify we aren't actually connected.
            if ($this->isConnected())
            {
                return true;
            }

            //determine the protocol suffix
            switch ($this->m_type)
            {
                case "imap":
                case "pop3":
                    $protocol = $this->m_type;
                    break;

                case "imaps":
                    $protocol = "imap/ssl";
                    break;
                case "pop3s":
                    $protocol = "pop3/ssl";
                    break;

                default:
                    throw new Exception("Invalid Protocol Type");
                    return false;
            }

            $connectionString = "{" . $this->m_hostname . ":" . $this->m_port . "/" . $protocol . "}" . $this->m_mailbox;

            //try connecting with 2 retries if keepalive is set and 0 if keepalive is not set
            $retries = ($this->m_keepAlive ? 2 : 0);
            $this->m_underlyingStream = imap_open($connectionString, $this->m_username, $this->m_password, 0, $retries);

            if (is_bool($this->m_underlyingStream))
            {
                $this->m_isClosed = true;
                return false;
            }
            return true;
        }

        /**
         * Closes the connection to the mail server.
         * 
         * @return Boolean If the connection was successfully closed.
         */
        public function close()
        {
            if ($this->m_isClosed)
            {
                return true;
            }

            return imap_close($this->m_underlyingStream, CL_EXPUNGE);
        }

        /**
         * Returns if the connection to the mail server is still established.
         * 
         * @return Boolean If the connection is still established.
         */
        public function isConnected()
        {
            if ($this->m_isClosed)
            {
                return false;
            }

            return $this->m_isClosed = !imap_ping($this->m_underlyingStream);
        }

        /**
         * Verifies the connection is still established and tries to reconnect if it isn't.
         *
         * @return Boolean If the connection is still established (or was successfully reconnected)
         */
        protected function keepConnected()
        {
            //verify we are still connected
            if (!$this->isConnected())
            {
                //if not connected and not keepalive
                if (!$this->m_keepAlive)
                {
                    return false;
                }

                //reconnect
                return $this->connect();
            }
            return true;
        }

        /**
         * Searches the MailStream for emails.
         * 
         * @param String $a_searchParameters The parameters to use when searching for the emails. See http://php.net/manual/en/function.imap-search.php
         * @param Boolean $a_pullData Specifies whether to retrieve the email data from the server. If this is false, the email data will be retrieved
         *  when it is requested from the MailEmail object.
         * @return Array An array of MailEmail objects found in the search sorted by date descending, or an empty array if none were found.
         */
        public function search($a_searchParameters, $a_pullData)
        {
            //if connection broke, return empty array
            if (!$this->keepConnected())
            {
                return array();
            }

            //search for results, if boolean false is returned, return an empty array
            $imapResults = imap_search($this->m_underlyingStream, $a_searchParameters);
            if ($imapResults === false)
            {
                return array();
            }
            rsort($imapResults);
            
            //parse 
            $mailObjects = array();
            foreach ($imapResults as $messageNumber)
            {
                $mailObject = MailEmail::createFromMessageNumber($this, $messageNumber, $a_pullData);

                if ($mailObject === false)
                {
                    continue;
                }

                array_push($mailObjects, $mailObject);
            }
            return $mailObjects;
        }

        /**
         * Reads message contents from the server.
         * 
         * @param MailEmail $a_mailEmail The email to read the contents of.
         * @return Boolean If the contents of the email were successfully read into the MailEmail object.
         */
        public function readMessageContents($a_mailEmail)
        {
            //verify still connected
            if (!$this->keepConnected())
            {
                return false;
            }

            $headers = imap_fetch_overview($this->m_underlyingStream, $a_mailEmail->getMessageNumber());
            if (empty($headers))
            {
                //message headers not found!
                return false;
            }
            $headers = $headers[0];

            //set fields from header
            if (isset($headers->seen))
            {
                $a_mailEmail->setSeen($headers->seen);
            }

            if (isset($headers->draft))
            {
                $a_mailEmail->setDraft($headers->draft);
            }

            if (isset($headers->subject))
            {
                $a_mailEmail->setSubject($headers->subject);
            }

            if (isset($headers->from))
            {
                $a_mailEmail->setFrom($headers->from);
            }

            if (isset($headers->to))
            {
                $a_mailEmail->setTo($headers->to);
            }

            if (isset($headers->date))
            {
                $a_mailEmail->setDate($headers->date);
            }

            //get the body
            $structure = imap_fetchstructure($this->m_underlyingStream, $a_mailEmail->getMessageNumber());
            if (!isset($structure->parts) || empty($structure->parts))
            {
                //message has no attachment
                $a_mailEmail->setBody($this->getMailPart($a_mailEmail, $structure));
            } else {
                //multipart
                $body = "";
                foreach ($structure->parts as $partNumber => $part)
                {
                    $addToBody = $this->getMailPart($a_mailEmail, $structure, $partNumber + 1);

                    if ($body != "" && $addToBody != "")
                    {
                        $body .= "\n\n";
                    }
                    $body .=  $addToBody;
                }
                $a_mailEmail->setBody($body);
            }
            return true;
        }

        /**
         * Retrieves a part of an email. Handles attachments, multipart and single part emails. If attachments
         *  are found, will create a MailAttachment object for each attachment and add it to the MailEmail.
         * 
         * @param MailEmail $a_mailEmail The MailEmail object to retrieve the part from.
         * @param Object $a_structure A structure returned from imap_fetchstructure.
         * @param Int $a_partNumber The index of the email part to process. Defaults to 0, single part emails should omit this parameter.
         * 
         * @return String The decoded string if the part is text.
         */
        private function getMailPart($a_mailEmail, $a_structure, $a_partNumber = 0)
        {
            $messageNumber = $a_mailEmail->getMessageNumber();

            $data = ($a_partNumber == 0) ?
                //multipart
                imap_fetchbody($this->m_underlyingStream, $messageNumber, $a_partNumber) :
                //simple
                imap_body($this->m_underlyingStream, $messageNumber);

            //decode data
            if ($a_structure->encoding == 4)
            {
                $data = quoted_printable_decode($data);
            } elseif ($a_structure->encoding == 3)
            {
                $data = base64_decode($data);
            }

            //get parameters of part
            $parameters = array();
            if ($a_structure->parameters)
            {
                foreach ($a_structure->parameters as $param)
                {
                    $parameters[strtolower($param->attribute)] = $param->value;
                }
            }
            if ($a_structure->dparameters)
            {
                foreach ($a_structure->dparameters as $param)
                {
                    $parameters[strtolower($param->attribute)] = $param->value;
                }
            }

            //the message text (if there is any)
            $text = "";

            //parse attachment
            if ((isset($parameters['filename']) && $parameters['filename']) || 
                (isset($parameters['name']) && $parameters['name']))
            {
                $filename = (isset($parameters['filename']) && $parameters['filename']) ? $parameters['filename'] : $parameters['name'];
                $a_mailEmail->addAttachment(new MailAttachment($a_mailEmail, $filename, $data));
            }

            //parse text
            if ($a_structure->type == 0 && $data)
            {
                if (strtolower($a_structure->subtype) == "plain")
                {
                    if ($text != "" && $data != "")
                    {
                        $text .= "\n\n";
                    }
                    $text .= trim($data);
                } else {
                    if ($text != "" && $data != "")
                    {
                        $text .= "<br /><br />";
                    }
                    $text .= $data;
                }
            } elseif ($a_structure->type == 2 && $data)
            {
                //embedded message
                if ($text != "" && $data != "")
                {
                    $text .= "\n\n";
                }
                $text .= $data;
            }

            //recurse into subparts
            if ($a_structure->parts)
            {
                foreach ($a_structure->parts as $partNumber => $part)
                {
                    $subpart = $this->getMailPart($a_mailEmail, $part, $a_partNumber . "." . ($partNumber + 1));
                    if (is_string($subpart) && $subpart != "")
                    {
                        //add the subpart text in
                        if ($text != "" && $subpart != "")
                        {
                            $text .= "\n\n";
                        }
                        $text .= $subpart;
                    }
                }
            }

            return $text;
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