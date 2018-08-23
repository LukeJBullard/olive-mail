<?php
    /**
     * OOP Email Structure for OliveWeb Framework Mail Module
     * 
     * @author Luke Bullard
     */

    class MailEmail
    {
        //message data
        private $m_subject;
        private $m_body;

        //message metadata
        private $m_from;
        private $m_to;
        private $m_date;
        private $m_seen;
        private $m_draft;

        //stream information and underlying message identifiers
        protected $m_msgNumber;
        protected $m_stream;

        //attachments
        protected $m_attachments;

        //flag for if the data was read or not
        private $m_dataRead;

        //private constructor because instantiation should take place in a factory
        private function __construct()
        {
            $this->m_dataRead = false;
            $this->m_attachments = array();
        }

        //getters
        /**
         * Retrieves the underlying message number for the Email.
         * 
         * @return Int The message number.
         */
        public function getMessageNumber()
        {
            return $this->m_msgNumber;
        }

        /**
         * Retrieves the message date as a string.
         * 
         * @return String The message date or an empty string if the date is not known.
         * @throws Exception If the message data is not loaded and can't be loaded from the server.
         */
        public function getDate()
        {
            //if the message hasn't been loaded, load it
            if (!$this->m_dataRead)
            {
                if (!$this->m_stream->readMessageContents($this))
                {
                    throw new Exception("Unable to read message contents.");
                    return "";
                }
                $this->setDataRead();
            }

            if (!isset($this->m_date))
            {
                return "";
            }

            return $this->m_date;
        }

        /**
         * Retrieves if the message has been marked 'seen'.
         * 
         * @return Boolean If the message has been explicitly marked 'seen' by the server.
         * @throws Exception If the message is not loaded and can't be loaded from the server.
         */
        public function getSeen()
        {
            //if the message hasn't been loaded, load it
            if (!$this->m_dataRead)
            {
                if (!$this->m_stream->readMessageContents($this))
                {
                    throw new Exception("Unable to read message contents.");
                    return "";
                }
                $this->setDataRead();
            }

            if (!isset($this->m_seen))
            {
                return false;
            }

            return $this->m_seen;
        }

        /**
         * Retrieves if the message is marked as a draft.
         * 
         * @return Boolean If the message is explicitly marked as a draft by the server. 
         * @throws Exception If the message is not loaded and can't be loaded from the server.
         */
        public function getDraft()
        {
            //if the message hasn't been loaded, load it
            if (!$this->m_dataRead)
            {
                if (!$this->m_stream->readMessageContents($this))
                {
                    throw new Exception("Unable to read message contents.");
                    return "";
                }
                $this->setDataRead();
            }

            if (!isset($this->m_draft))
            {
                return false;
            }

            return $this->m_draft;
        }

        /**
         * Retrieves the message subject.
         * 
         * @return String The message subject or an empty string if the subject is not known or is omitted.
         * @throws Exception If the message subject is not loaded and can't be loaded from the server.
         */
        public function getSubject()
        {
            //if the message hasn't been loaded, load it
            if (!$this->m_dataRead)
            {
                if (!$this->m_stream->readMessageContents($this))
                {
                    throw new Exception("Unable to read message contents.");
                    return "";
                }
                $this->setDataRead();
            }

            if (!isset($this->m_subject))
            {
                return "";
            }

            return $this->m_subject;
        }

        /**
         * Retrieves the message body.
         * 
         * @return String The message body or an empty string if the body is not loaded or is empty.
         * @throws Exception If the message body is not loaded and can't be loaded from the server.
         */
        public function getBody()
        {
            //if the message hasn't been loaded, load it
            if (!$this->m_dataRead)
            {
                if (!$this->m_stream->readMessageContents($this))
                {
                    throw new Exception("Unable to read message contents.");
                    return "";
                }
                $this->setDataRead();
            }

            if (!isset($this->m_body))
            {
                return "";
            }

            return $this->m_body;
        }

        /**
         * Retrieves the message from address.
         * 
         * @return String The message from address or an empty string if the from address is not loaded or is empty.
         * @throws Exception If the message is not loaded and can't be loaded from the server.
         */
        public function getFrom()
        {
            //if the message hasn't been loaded, load it
            if (!$this->m_dataRead)
            {
                if (!$this->m_stream->readMessageContents($this))
                {
                    throw new Exception("Unable to read message contents.");
                    return "";
                }
                $this->setDataRead();
            }

            if (!isset($this->m_from))
            {
                return "";
            }

            return $this->m_from;
        }

        /**
         * Retrieves the message to address.
         * 
         * @return String The message to address or an empty string if the to address is not loaded or is empty.
         * @throws Exception If the message is not loaded and can't be loaded from the server.
         */
        public function getTo()
        {
            //if the message hasn't been loaded, load it
            if (!$this->m_dataRead)
            {
                if (!$this->m_stream->readMessageContents($this))
                {
                    throw new Exception("Unable to read message contents.");
                    return "";
                }
                $this->setDataRead();
            }

            if (!isset($this->m_to))
            {
                return "";
            }

            return $this->m_to;
        }

        /**
         * Retrieves the attachments from the MailEmail object.
         * 
         * @return Array An array of MailAttachment objects.
         */
        public function getAttachments()
        {
            //if the array is not setup, create it
            if (!isset($this->m_attachments) || !is_array($this->m_attachments()))
            {
                $this->m_attachments = array();
            }

            return (new ArrayObject($this->m_attachments))->getArrayCopy();
        }

        /**
         * Retrieves an attachment from the MailEmail object baased on filename.
         * 
         * @param String $a_filename The filename of the attachment to retrieve.
         * @return MailAttachment|Boolean The retrieved MailAttachment object or
         *  boolean false if it could not be found.
         */
        public function getAttachment($a_filename)
        {
            //if the attachments array is not setup, create it
            if (!isset($this->m_attachments) || !is_array($this->m_attachments()))
            {
                $this->m_attachments = array();
                return false;
            }

            foreach ($this->m_attachments as $attachment)
            {
                if ($attachment->getFilename() == $a_filename)
                {
                    return $attachment;
                }
            }

            //the attachment was not found, return boolean false
            return false;
        }

        //setters
        /**
         * Sets the data read flag to true, signifying the data has been read from
         *  the mail server into this structure.
         */
        private function setDataRead()
        {
            $this->m_dataRead = true;
        }

        /**
         * Sets the date the email was sent.
         * 
         * @param String $a_date The date the email was sent.
         */
        public function setDate($a_date)
        {
            //if the field is already set, return
            if (isset($this->m_date))
            {
                return;
            }

            $this->m_date = $a_date;
        }

        /**
         * Sets if the message was marked 'seen' by the server.
         * 
         * @param Boolean $a_seen If the message was marked 'seen' by the server.
         */
        public function setSeen($a_seen)
        {
            //if the field is already set, return
            if (isset($this->m_seen))
            {
                return;
            }

            $this->m_seen = $a_seen;
        }

        /**
         * Sets if the message was marked 'draft' by the server.
         * 
         * @param Boolean $a_draft If the message was marked 'draft' by the server.
         */
        public function setDraft($a_draft)
        {
            //if the field is already set, return
            if (isset($this->m_draft))
            {
                return;
            }

            $this->m_draft = $a_draft;
        }

        /**
         * Sets the message from address.
         * 
         * @param String $a_from The from email address.
         */
        public function setFrom($a_from)
        {
            //if the field is already set, return
            if (isset($this->m_from))
            {
                return;
            }

            $this->m_from = $a_from;
        }

        /**
         * Sets the message to address.
         * 
         * @param String $a_to The to email address.
         */
        public function setTo($a_to)
        {
            //if the field is already set, return
            if (isset($this->m_to))
            {
                return;
            }

            $this->m_to = $a_to;
        }

        /**
         * Sets the message subject.
         * 
         * @param String $a_subject The message subject.
         */
        public function setSubject($a_subject)
        {
            //if the field is already set, return
            if (isset($this->m_subject))
            {
                return;
            }

            $this->m_subject = $a_subject;
        }

        /**
         * Sets the message body.
         * 
         * @param String $a_body The message body.
         */
        public function setBody($a_body)
        {
            //if the field is already set, return
            if (isset($this->m_body))
            {
                return;
            }

            $this->m_body = $a_body;
        }

        /**
         * Adds an email attachment to the MailEmail.
         * 
         * @param MailAttachment $a_attachment The attachment to add.
         */
        public function addAttachment($a_attachment)
        {
            if (!isset($this->m_attachments) || !is_array($this->m_attachments))
            {
                $this->m_attachments = array();
            }

            array_push($this->m_attachments, $a_attachment);
        }

        /**
         * @param MailStream $a_mailStream The MailStream associated with the message.
         * @param Int $a_messageNumber The underlying message number for the message.
         * @param Boolean $a_readNow Whether or not to read the message contents from
         *  the MailStream upon creation of the MailEmail object.
         * 
         * @return MailEmail|Boolean The created MailEmail object or Boolean False if the message could not be created.
         * @throws Exception If $a_readNow is true and the message contents can't be read from the MailStream.
         */
        public static function createFromMessageNumber($a_mailStream, $a_messageNumber, $a_readNow)
        {
            $mailObj = new MailEmail();
            $mailObj->m_stream = $a_mailStream;
            $mailObj->m_messageNumber = $a_messageNumber;

            //if message contents should be read now, read it in
            if ($a_readNow)
            {
                if (!$mailObj->m_stream->readMessageContents($mailObj))
                {
                    throw new Exception("Unable to read message contents.");
                    return false;
                }
                $mailObj->setDataRead();
            }

            return $mailObj;
        }

        /**
         * Marks the email to be deleted from the server.
         * 
         * @param Boolean $a_expunge If the emails marked for deletion should be deleted in this command
         *  rather than waiting for the expunge to happen normally.
         * @return Boolean If the email was successfully marked to be deleted from the server.
         */
        public function delete($a_expunge = false)
        {
            return $this->m_stream->deleteEmail($this, $a_expunge);
        }
    }
?>