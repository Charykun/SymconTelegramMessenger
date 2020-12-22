<?php
    if (file_exists("../functions.ips.php")) include_once("../functions.ips.php");

    class TelegramMessenger extends IPSModule {
        
        public function Create() {
            parent::Create();
            $this->RegisterPropertyString("BotID", "123456789:ABCDEFGHIJKLMNOPQRSTUVWXYZ");
            $this->RegisterPropertyString("UserList", "");
            $this->RegisterPropertyBoolean("FetchIncoming", TRUE);
            $this->RegisterPropertyBoolean("ProcessIncoming", TRUE);
            $this->RegisterPropertyInteger("ProcessIncomingSkript", 0);
            $this->RegisterTimer("GetUpdates", 5000, 'Telegram_GetUpdates($_IPS[\'TARGET\']);');
        }

        public function ApplyChanges() {
            parent::ApplyChanges();
        }

        private function Send($chat_id, $text, $pares_mode, $replay_markup) {
            if (!is_numeric($chat_id)) {
                $userlist = json_decode($this->ReadPropertyString("UserList"));
                foreach($userlist as $user) {
                    if ($user->user == $chat_id)
                        $chat_id = $user->chat_id;
                }
            }
            include_once(__DIR__ . "/../libs/Telegram.php");
            $telegram = new Telegram($this->ReadPropertyString("BotID"));
            $content = array('chat_id' => $chat_id, 'text' => $text, 'parse_mode' => $pares_mode, 'reply_markup' => $replay_markup);
            return $telegram->sendMessage($content);
        }

        public function SendText($chat_id, string $text) {
            return $this->Send($chat_id, $text, "Markdown", "");
        }

        public function SendTextKey($chat_id, string $text, string $replay_markup) {
            return $this->Send($chat_id, $text, "Markdown", $replay_markup);
        }

        public function SendHTML($chat_id, string $text) {
            return $this->Send($chat_id, $text, "HTML", "");
        }

        public function SendHTMLKey($chat_id, string $text, string $replay_markup) {
            return $this->Send($chat_id, $text, "HTML", $replay_markup);
        }

        public function DeleteMessage($chat_id, int $message_id) {
            if (!is_numeric($chat_id)) {
                $userlist = json_decode($this->ReadPropertyString("UserList"));
                foreach($userlist as $user) {
                    if ($user->user == $chat_id)
                        $chat_id = $user->chat_id;
                }
            }
            include_once(__DIR__ . "/../libs/Telegram.php");
            $telegram = new Telegram($this->ReadPropertyString("BotID"));
            $content = array('chat_id' => $chat_id, 'message_id' => $message_id);
            return $telegram->deleteMessage($content);
        }

        public function EditMessageReplyMarkup($chat_id, int $message_id, string $replay_markup) {
            if (!is_numeric($chat_id)) {
                $userlist = json_decode($this->ReadPropertyString("UserList"));
                foreach($userlist as $user) {
                    if ($user->user == $chat_id)
                        $chat_id = $user->chat_id;
                }
            }
            include_once(__DIR__ . "/../libs/Telegram.php");
            $telegram = new Telegram($this->ReadPropertyString("BotID"));
            $pares_mode = "Markdown";
            $content = array('chat_id' => $chat_id, 'message_id' => $message_id, 'parse_mode' => $pares_mode, 'reply_markup' => $replay_markup);
            return $telegram->editMessageReplyMarkup($content);
        }

        public function EditMessageText($chat_id, int $message_id, string $text, string $replay_markup) {
            if (!is_numeric($chat_id)) {
                $userlist = json_decode($this->ReadPropertyString("UserList"));
                foreach($userlist as $user) {
                    if ($user->user == $chat_id)
                        $chat_id = $user->chat_id;
                }
            }
            include_once(__DIR__ . "/../libs/Telegram.php");
            $telegram = new Telegram($this->ReadPropertyString("BotID"));
            $pares_mode = "Markdown";
            $content = array('chat_id' => $chat_id, 'message_id' => $message_id, 'text' => $text, 'parse_mode' => $pares_mode, 'reply_markup' => $replay_markup);
            return $telegram->editMessageText($content);
        }

        public function SendImage($chat_id, string $image_path, string $text) {
            if (!is_numeric($chat_id)) {
                $userlist = json_decode($this->ReadPropertyString("UserList"));
                foreach($userlist as $user) {
                    if ($user->user == $chat_id)
                        $chat_id = $user->chat_id;
                }
            }
            include_once(__DIR__ . "/../libs/Telegram.php");
            $telegram = new Telegram($this->ReadPropertyString("BotID"));
            $image_info = getimagesize($image_path);
            $mime = $image_info["mime"];
            if ($mime == "image/jpeg" or $mime == "image/jpg") {
                $ext = ".jpg";
            } else if ($mime == "image/png") {
                $ext = ".png";
            } else if ($mime == "image/gif") {
                $ext = ".gif";
            } else {
                return FALSE;
            }
            $img = curl_file_create($image_path, $mime, md5($image_path.time()).$ext);
            $content = array('chat_id' => $chat_id, 'caption' => $text, 'photo' => $img);
            return $telegram->sendPhoto($content);
        }

        public function SendDocument($chat_id, string $document_path, string $mimetyp, string $text) {
            if (!is_numeric($chat_id)) {
                $userlist = json_decode($this->ReadPropertyString("UserList"));
                foreach($userlist as $user) {
                    if ($user->user == $chat_id)
                        $chat_id = $user->chat_id;
                }
            }
            include_once(__DIR__ . "/../libs/Telegram.php");
            $telegram = new Telegram($this->ReadPropertyString("BotID"));
            $ext = pathinfo($document_path);
            $doc = curl_file_create($document_path, $mimetyp, md5($document_path.time()).".".$ext["extension"]);
            $content = array('chat_id' => $chat_id, 'caption' => $text, 'document' => $doc);
            return $telegram->SendDocument($content);
        }

        public function GetUpdates() {
            if ($this->ReadPropertyBoolean("FetchIncoming")) {
                include_once(__DIR__ . "/../libs/Telegram.php");
                $telegram = new Telegram($this->ReadPropertyString("BotID"));
                $req = $telegram->getUpdates();
                for ($i = 0; $i < $telegram->UpdateCount(); $i++) {
                    $telegram->serveUpdate($i);
                    $update_type = $telegram->getUpdateType();
                    $text = $telegram->Text();
                    $chat_id = $telegram->ChatID();
                    $message_id = $telegram->MessageID();
                    $username = $telegram->Username();
                    $date = $telegram->Date();
                    $this->SendDebug("$username ($chat_id)", $text, 0);
                    IPS_LogMessage("Telegram", "Update von " . $chat_id . " -> " . $text . " / " . $date . " / " . print_r($telegram,true));
                    if ($this->ReadPropertyBoolean("ProcessIncoming") && ( (time() - $date) < 60  or  $update_type != "message"  ) ) {
                        $userlist = json_decode($this->ReadPropertyString("UserList"));
                        foreach($userlist as $user) {
                            if ($user->chat_id == $chat_id) {
                                IPS_RunScriptEx(
									$this->ReadPropertyInteger("ProcessIncomingSkript"),
									array("SENDER" => "Telegram", "INSTANCE" => $this->InstanceID, "UPDATETYPE" => $update_type, "CHATID" => $chat_id, "MESSAGEID" => $message_id, "USER" => $username, "VALUE" => $text)
								);
                            }
                        }
                    }
                }
            }
        }
        
    }
?>