<?php
    /**
     * OOP IMAP Email Attachment Structure for OliveWeb Framework Mail Module
     * 
     * @author Luke Bullard
     */

    class MailAttachment
    {
        protected $m_mailEmail;

        protected $m_filename;
        protected $m_data;

        /**
         * Constructor for MailAttachment
         * 
         * @param MailEmail $a_mailEmail The MailEmail that the attachment is part of.
         * @param String $a_filename The filename of the attachment.
         * @param String $a_data The decoded data of the attachment.
         */
        public function __construct($a_mailEmail, $a_filename, $a_data)
        {
            $this->m_mailEmail = $a_mailEmail;
            $this->m_filename = $a_filename;
            $this->m_data = $a_data;
        }

        /**
         * Retrieves the filename of the attachment.
         * 
         * @return String The filename of the attachment, or a blank string if the filename is not set.
         */
        public function getFilename()
        {
            if (isset($this->m_filename))
            {
                return $this->m_filename;
            }

            return "";
        }
        
        /**
         * Retrieves the data of the attachment.
         * 
         * @return String The decoded data of the attachment or a blank string if the data is not set.
         */
        public function getData()
        {
            if (isset($this->m_data))
            {
                return $this->m_data;
            }

            return "";
        }

        /**
         * Saves the attachment to a file.
         * 
         * @param String $a_filename The filename/path to save the attachment to.
         * @return Boolean If the file was successfully saved.
         */
        public function saveFile($a_filename)
        {
            //invalid parameter
            if (!is_string($a_filename))
            {
                return false;
            }

            if (file_put_contents($a_filename, $this->getData()) === false)
            {
                //write not successful
                return false;
            }

            return true;
        }
    }
?>