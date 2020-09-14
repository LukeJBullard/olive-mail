<?php
    /**
     * Mail module for OliveWeb
     * 
     * @author Luke Bullard
     */

    //make sure we are included securely
    if (!defined("INPROCESS")) { header("HTTP/1.0 403 Forbidden"); exit(0); }

    /**
     * The Mail OliveWeb Module
     */
    class MOD_mail
    {
        protected $m_streams;
        protected $m_streamConfigs;

        public function __construct()
        {
            //load mod src files
            require_once("src/MailEmail.php");
            require_once("src/MailAttachment.php");
            require_once("src/MailStream.php");

            //get preset stream configs
            $this->m_streamConfigs = array();
            include_once(dirname(__FILE__) . "/config.php");

            if (isset($mail_config))
            {
                foreach ($mail_config as $configName => $config)
                {
                    //if a config already exists for this name, skip
                    if (isset($this->m_streamConfigs[$configName]))
                    {
                        continue;
                    }

                    $hostname = (isset($config['hostname']) ? $config['hostname'] : "");
                    $type = (isset($config['type']) ? strtolower($config['type']) : "");
                    $port = (isset($config['port']) ? intval($config['port']) : -1);
                    $username = (isset($config['username']) ? $config['username'] : "");
                    $password = (isset($config['password']) ? $config['password'] : "");
                    $mailbox = (isset($config['mailbox']) ? $config['mailbox'] : "INBOX");
                    
                    //if config parameters invalid, skip
                    if ($hostname == "" || $username == "")
                    {
                        continue;
                    }

                    //set defaults on non-essential parameters
                    if ($type == "")
                    {
                        $type = "imap";
                    }

                    if ($port == -1)
                    {
                        $port = MailStream::getDefaultPort($type);
                        
                        //if the port was not found in the defaults, skip
                        if ($port === false)
                        {
                            continue;
                        }
                    }

                    //add the config to the list
                    $this->m_streamConfigs[$configName] = array(
                        "hostname" => $hostname,
                        "type" => $type,
                        "port" => $port,
                        "username" => $username,
                        "password" => $password,
                        "mailbox" => $mailbox
                    );
                }
            }
        }

        /**
         * Opens a MailStream based on a config set in the config.php file, or returns
         *  an already established and connected MailStream if one exists.
         * 
         * @param String $a_configName The name of the config to use.
         * @return MailStream|Boolean The connected MailStream object or Boolean False
         *  if the connection could not be established.
         */
        public function getStream($a_streamName)
        {
            //if a stream already exists, verify it's connected then use it.
            if (isset($this->m_streams[$a_streamName]))
            {
                if ($this->m_streams[$a_streamName]->isConnected())
                {
                    return $this->m_streams[$a_streamName];
                }

                //stream is not connected, close it and remove it from the list, then continue on
                $this->m_streams[$a_streamName]->close();
                unset($this->m_streams[$a_streamName]);
            }

            //try connecting the stream
            $streamConfig = $this->m_streamConfigs[$a_streamName];

            try {
                $stream = new MailStream($streamConfig['hostname'], $streamConfig['port'], $streamConfig['type'], $streamConfig['mailbox'], $streamConfig['username'], $streamConfig['password']);

                if ($stream->connect())
                {
                    $this->m_streams[$a_streamName] = $stream;
                    return $stream;
                }
            } catch (Exception $e) {
                return false;
            }

            return false;
        }
    }
?>