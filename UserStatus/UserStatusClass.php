<?php

class UserStatusClass {
    
	/* private */ function __construct($u_id) {
                global $wgOut, $wgScriptPath;
                $wgOut->addScriptFile($wgScriptPath.'/extensions/SocialProfile/UserStatus/UserStatus.js' );
	}
    
    public function getStatus($u_id) {
        $dbr = wfGetDB(DB_SLAVE);
        $res = $dbr->select('user_status', '*', array('us_user_id' => $u_id), __METHOD__);
        $message = array();
        if (empty($res)) {
            $message = '';
        } else {
            foreach ($res as $row) {
                $message = array(
                    'us_user_id' => $row->us_user_id,
                    'us_status' => htmlspecialchars($row->us_status),
                );
            }
        }
        return $message;
    }

    /*
     * 
     */

    public function setStatus($u_id, $message) {
        if (mb_strlen($message) > 140) { // change
            //ERROR. Message lenth is too long
            return;
        }
        $dbw = wfGetDB(DB_MASTER);
        $res = $dbw->select('user_status', '*', array('us_user_id' => $u_id), __METHOD__);
        $i = 0;
        foreach ($res as $row)
            $i++;
        if ($i == 0) {
            $dbw->insert(
                    'user_status',
                    /* SET */ array(
                'us_user_id' => $u_id,
                'us_status' => $message,), __METHOD__
            );
        } else {
            $dbw->update(
                    'user_status',
                    /* SET */ array('us_status' => $message),
                    /* WHERE */ array('us_user_id' => $u_id), __METHOD__
            );
        }
        $this->useStatusHistory('insert',$u_id);
        return;
    }

    /*
     * Method that manipulates the user_status_history table
     * $mode - varieble for realization of two methods. 
     * Variants:
     *  'insert'
     *  'select'
     */
    public function useStatusHistory($mode,$u_id) {
        $dbw = wfGetDB(DB_MASTER);
        $userHistory = $dbw->select('user_status_history', '*', array('ush_user_id' => $u_id), __METHOD__, array('ORDER BY' => 'ush_timestamp ASC'));
        $i = 0;
        $history = array();
        foreach ($userHistory as $row) {
            $i++;
            $history[] = array(
                'ush_id' => $row->ush_id,
                'ush_user_id' => $row->ush_user_id,
                'ush_timestamp' => $row->ush_timestamp,
                'ush_status' => $row->ush_status,
            );
        }
        if ($mode=='select') return $history;  
        if ($mode=='insert'){
            $currentStatus = $this->getStatus($u_id);

            if ($i < 4) {
                $dbw->insert(
                        'user_status_history',
                        /* SET */ array(
                        'ush_user_id' => $u_id,
                        'ush_status' => $currentStatus['us_status']), __METHOD__
                );
            } else {
                $dbw->update(
                        'user_status_history',
                        /* SET */ array('ush_status' => $currentStatus['us_status']), 
                        /*WHERE*/ array('ush_user_id' => $u_id,
                                  'ush_timestamp' => $history[0]['ush_timestamp']),
                                  __METHOD__);
            }
            return;
        }
    }
}