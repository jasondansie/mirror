<?php
 class dataConnection { 
	public $_Link;
	public $debug=true;
	public $userid=0;

	// Construct: Open SQL
	public function __construct()
	{
            $this->openDatabase();
	}
  
	// Destruct: Close SQL
	public function __destruct() {
            $this->closeDatabase();
	}

	public function setUser($uid){
		$this->userid = $uid;
	}
 
	public function openDatabase()
	{
            $this->_Link = mysqli_connect(SITEDB_HOSTNAME, SITEDB_USERNAME, SITEDB_PASSWORD);
            mysqli_query($this->_Link, "SET NAMES 'utf8'");
            $db = mysqli_select_db($this->_Link, SITEDB_DATABASE);
            if (!$this->_Link) {
                $this->callError("Server connection error: " . SITEDB_HOSTNAME);
            }
            if(!$db) {
                $this->callError("Database open error: " . SITEDB_DATABASE);
            }
    }

	public function closeDatabase()
	{
		mysqli_close($this->_Link);
	}
	

	public function loadPageContent($loadpage)
	{
	$sql = sprintf("
                        SELECT      P.pageid, P.title, P.content, P.shorturl, P.featureimageurl
                        FROM        page P
                        WHERE       P.shorturl = '%s'
                        LIMIT       0,1
	",
					$this->esc($loadpage)
			);

	$result = mysqli_query($this->_Link, $sql);
	if (!$result) {
            $this->callError("Result error: " . $sql);
        }
        $row = mysqli_fetch_object($result);
	mysqli_free_result($result);
	return $row;
	}
				 
	
	// Get User Data
	public function processLogin($username, $password){
                $this->setUser(0);
		$sql = sprintf("
			SELECT		u.userid
			FROM		users u
			WHERE		(u.email = '%s')
                                AND     (u.password = '%s')
                                AND     (u.enabled = 1)
		",
			$this->esc($username),
                        $this->esc($this->getSHA($password . SITE_KEY))
		);
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError($sql);
        }
        $queryresult  = mysqli_fetch_array($result);
		mysqli_free_result($result);
                if (is_array($queryresult)) {
            $this->setUser($queryresult[0]);
        }
    }
  
        
         // Get User Data
	public function getUserInfoWithEmail($email){
		$sql = sprintf("
			SELECT		u.userid, u.firstname, u.lastname, u.email, u.resetpassword, u.company, u.position, u.photofilename, u.createdate, u.author_description, u.author_title, u.author_sig, u.blog_image
			FROM		users u
			WHERE		u.email = '%s'
		",
			$this->esc($email)
		);
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
                    $this->callError($sql);
                }
                $queryresult  = mysqli_fetch_object($result);
		mysqli_free_result($result);
                                    
		return $queryresult;
	}

        // Request Reset (or try) Password
	public function tryPassReset($email, $temppassword){
		$is_updated = false;
		if(trim($email)!= ""){
			$sql = sprintf("
				UPDATE		users
				SET		resetpassword='%s', resetpasswordtime=NOW()
				WHERE		(email='%s')
					AND	(
                                                    (resetpasswordtime < date_sub(now(), interval 1 day))
                                                    OR
                                                    (resetpasswordtime is null)
						)
			",
				$temppassword, $this->esc($email)
			);
			mysqli_query($this->_Link, $sql) or $this->callError($sql);
			if (mysqli_affected_rows($this->_Link) == 1) {
                $is_updated = true;
            }
        }
		return $is_updated;
        }

        // Activate Password Reset (or try)
        public function resetPassword($resetcode){
                $is_updated = false;
                $resetinfo = $this->getEmailWithResetCode($resetcode);
                if(is_object($resetinfo)){
                    if(trim($resetcode) != ""){ 
                        $tpassword = $this->getSHA($resetcode . SITE_KEY);
                        $sql = sprintf("
                                UPDATE      users
                                SET         resetpassword='', resetpasswordtime=null, password='%s'
                                WHERE       (resetpasswordtime > date_sub(now(), interval 1 day))
                                        AND (resetpassword = '%s')
                                        AND (resetpassword != '')
                        ",
                                $this->esc($tpassword), $this->esc($resetcode)
                        );
                        mysqli_query($this->_Link, $sql) or $this->callError($sql);
                        if (mysqli_affected_rows($this->_Link) == 1) {
                    $is_updated = true;
                }
            }
                }
                return $is_updated;
        }
        
        // Get User Data
	public function getEmailWithResetCode($resetcode){
		$sql = sprintf("
			SELECT		u.email
			FROM		users u
			WHERE		u.resetpassword = '%s'
		",
			$this->esc($resetcode)
		);
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError($sql);
        }
        $queryresult  = mysqli_fetch_object($result);
		mysqli_free_result($result);
                                    
		return $queryresult;
	}

	// Get User Data
	public function getUserInfo($userid=0){
                $tuserid = $this->userid;
                if ($userid != 0) {
            $tuserid = $this->esc($userid);
        }

        $sql = sprintf("
			SELECT		u.userid, u.firstname, u.lastname, u.email, u.createddate, u.photofilename,u.position, u.company, u.startdate, u.author_description, u.author_title, u.author_sig, u.author_image,
                                        a.accessid, a.name AS accessname
			FROM		users u
                        INNER JOIN      useraccess a ON a.accessid = u.accessid
			WHERE		u.userid = %d
		",
			$tuserid
		);
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
                    $this->callError($sql);
                }
                $queryresult  = mysqli_fetch_object($result);
		mysqli_free_result($result);
                                    
		return $queryresult;
	}
        

        // Update User Profile
        public function setUserInfo($email, $firstname, $lastname)
        {

		$sql = sprintf("
                        UPDATE      users
			SET         email='%s', firstname='%s', lastname='%s'
			WHERE       (userid=%d)
		",
                        $this->esc($email), $this->esc($firstname), $this->esc($lastname), 
                        $this->userid
		);
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError($sql);
        }
    }
        // Update User Profile
        public function setUserimage($imageloc)
        {

		$sql = sprintf("
                        UPDATE      users
			SET         photofilename='%s'
			WHERE       (userid=%d)
		",
                       $this->esc($imageloc), $this->userid
		);
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError($sql);
        }
    }
        
        // Update User Profile
        public function setUserPass($userid, $pass)
        {

		$sql = sprintf("
                        UPDATE      users
			SET         password='%s'
			WHERE       (userid=%d)
		",
                        $this->esc($pass),$this->userid
		);
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError($sql);
        }
    }
  

        // Update User Password
        public function setUserPassword($password){
		$sql = sprintf("
                        UPDATE      users
			SET         password='%s'
			WHERE       (userid=%d)
		",
                        $this->esc($this->getSHA($password . SITE_KEY)), $this->userid
		);
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError($sql);
        }
    }
        // Update User Password
        public function setUserPasswordWithId($password, $id){
		$sql = sprintf("
                        UPDATE      users
			SET         password='%s'
			WHERE       (userid=%d)
		",
                        $this->esc($this->getSHA($password . SITE_KEY)), $this->esc($id)
		);
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError($sql);
        }
    }
        // Add contact us info to database
        public function addContactus($email, $reason, $info){
		$sql = sprintf("
			INSERT INTO      contactus
			               (createdate, email, reason, info)
			VALUES		(NOW(), '%s', '%s', '%s')
		",
                        $this->esc($email), $this->esc($reason), $this->esc($info)                       
		);
		mysqli_query($this->_Link, $sql) or $this->callError($sql);
		return mysqli_insert_id($this->_Link);
        }
        
        // Getcontact us Data
	public function getContactUsData(){
		$sql = sprintf("
			SELECT		c.contactid, c.reason, c.info, c.email, c.createdate
			FROM		contactus c
			
		"
		);
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError("Result error: " . $sql);
        }
        $queryresult = array();
		while ($row = mysqli_fetch_object($result)) {
                    $queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;
	}
        
         // Getcontact us Data
	public function getContactUsDataByID($contactid){
		$sql = sprintf("
			SELECT		c.contactid, c.reason, c.info, c.email, c.createdate, notes
                                        
			FROM		contactus c
			WHERE		c.contactid = %d
                                   
		",
			$this->esc($contactid)
		);
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
                    $this->callError($sql);
                }
                $queryresult  = mysqli_fetch_object($result);
		mysqli_free_result($result);
		return $queryresult;
        }
        
        // Getcontact us Data
	public function updatContactUsData($contactid, $notes){
            $is_updated = false;
            $sql = sprintf("
                    UPDATE		contactus
                    SET		notes='%s'
                    WHERE		(contactid=%d)
            ",
                     $this->esc($notes), $this->esc($contactid)
            );
            mysqli_query($this->_Link, $sql) or $this->callError($sql);
            if (mysqli_affected_rows($this->_Link) == 1) {
                $is_updated = true;
            }
                return $is_updated;
        }
 
        // Access List
 	public function accessList(){
		$sql = sprintf("
                        SELECT      a.accessid, a.name,
                        FROM        useraccess a
		");
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError($sql);
        }
        $queryresult = array();
		while ($row = mysqli_fetch_object($result)) {
                    $queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;
	}
        public function setAccess(){
            $accesslist = $this->userAccessList($this->userid);
            foreach($accesslist as $access){
                if (!is_null($access->userid)) {
                $this->access[$access->page] = true;
            } else {
                $this->access[$access->page] = false;
            }
        }
        }
        
        public function userAccessList($userid){
		$sql = sprintf("
                        SELECT      a.accessid, a.name, ul.userid, a.page, a.type, a.table_name, a.base_value, a.search_column, a.useofhours
                        FROM        useraccess a
                        LEFT OUTER JOIN useraccesslink ul
                                   ON ul.userid = %d AND ul.accessid = a.accessid 
		",
                        $this->esc($userid)
                );
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError($sql);
        }
        $queryresult = array();
		while ($row = mysqli_fetch_object($result)) {
                    $queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;            
        }
        
        public function checkUserAccess($userid, $page){
		$sql = sprintf("
                        SELECT      a.accessid, a.name, ul.userid
                        FROM        useraccess a
                        LEFT OUTER JOIN useraccesslink ul
                                    ON ul.userid = %d AND ul.accessid = a.accessid 
                        WHERE       a.page = '%s'
		",
                        $this->esc($userid), $this->esc($page)
                );
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
                    $this->callError($sql);
                }
                $queryresult = array();
		$row = mysqli_fetch_object($result);
		mysqli_free_result($result);
		return $row;
        }      
        
        public function loguser($ip, $username, $success){
		$sql = sprintf("
			INSERT INTO	signinlog
                                        (date, ip, username, success)
			VALUES		(NOW(), '%s', '%s', '%s')
		",
			$this->esc($ip), $this->esc($username), $this->esc($success)
		);
		$result = mysqli_query($this->_Link, $sql);
                if (!$result) {
            $this->callError($sql);
        }
    }
 	
        
	public function adminUserList(){
		$sql = sprintf("
			SELECT		u.userid, u.createddate, u.firstname, u.lastname,                                         
                                        u.email, u.accessid
			FROM		users u
                        WHERE           (u.enabled = 1)
                        ORDER BY        u.lastname, u.firstname
		",
			$this->userid
		);
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError("Result error: " . $sql);
        }
        $queryresult = array();
		while ($row = mysqli_fetch_object($result)) {
                    $queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;
	}

        // Erase User
        public function adminEraseUser($userid){
		$sql = sprintf("
                        UPDATE      users
			SET         enabled=0
			WHERE       (userid=%d)
		",
                        $this->esc($userid)
		);
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError("SQL Error: " . $sql);
        }
    }

        // Add A User
        public function adminAddUser($accessid, $email, $firstname, $lastname, $password){
		$sql = sprintf("
			INSERT INTO	users
                                        (accessid, createddate, createdby, email, firstname, lastname, password, enabled)
			VALUES		(%d, NOW(), %d, '%s', '%s', '%s', '%s', 1)
		",
			$this->esc($accessid), $this->userid, $this->esc($email), $this->esc($firstname),
                        $this->esc($lastname), $this->esc($this->getSHA($password . SITE_KEY))
		);
		mysqli_query($this->_Link, $sql) or $this->callError($sql);
		return mysqli_insert_id($this->_Link);
        }
        
        // Edit User
        public function adminEditUser($userid, $accessid, $email, $firstname, $lastname){
		$sql = sprintf("
			UPDATE          users
                        SET             accessid=%d, email='%s', firstname='%s', lastname='%s'
                        WHERE           (userid = %d)
		",
			$this->esc($accessid), $this->esc($email),
                        $this->esc($firstname), $this->esc($lastname), 
                        $this->esc($userid)
		);
		$result = mysqli_query($this->_Link, $sql);
                if (!$result) {
            $this->callError($sql);
        }
    }        

        // Edit User Password
        public function adminResetPassword($userid, $newpass){
		$sql = sprintf("
			UPDATE          users
                        SET             password='%s'
                        WHERE           (userid = %d)
		",
			$this->esc($this->getSHA($newpass . SITE_KEY)), $this->esc($userid)
		);
		$result = mysqli_query($this->_Link, $sql);
                if (!$result) {
            $this->callError($sql);
        }
    }
        
        // set user access
	public function setAccessIDs($userid, $accessid){
                $sql = sprintf("
                        INSERT INTO	useraccesslink
                                        (userid, accessid)
                        VALUES		(%d,%d)
                ",
                         $this->esc($userid), $this->esc($accessid)
                );
                mysqli_query($this->_Link, $sql) or $this->callError($sql);
                if (mysqli_affected_rows($this->_Link) == 1) {
            $is_updated = true;
        }
        return $is_updated;
        }
        
        // get User Access
        public function getAccessIDs(){
		$sql = sprintf('
			SELECT		c.accessid, c.name
			FROM		useraccess c
                        ORDER BY        c.accessid ASC
		');
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError("Result error: " . $sql);
        }
        $queryresult = array();
		while ($row = mysqli_fetch_object($result)) {
			$queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;
        }
        // get User Access
        public function getAccessIDsByID($userid){
		$sql = sprintf('
			SELECT		c.accessid, c.name, c.type, ul.accessid AS "useraccessid", ul.userid
			FROM		useraccess c
                        LEFT OUTER JOIN useraccesslink ul
                                   ON ul.userid = %d AND ul.accessid = c.accessid
                        ORDER BY        c.accessid ASC
		',
                         $this->esc($userid)
                );
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError($sql);
        }
        $queryresult = array();
		while ($row = mysqli_fetch_object($result)) {
                    $queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;     
        }
        // get equipmnet ids
        public function getEquipmentIDs(){
		$sql = sprintf('
			SELECT		c.eqid, c.item
			FROM		equipment c                         
                        ORDER BY        c.eqid ASC
		');
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError("Result error: " . $sql);
        }
        $queryresult = array();
		while ($row = mysqli_fetch_object($result)) {
			$queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;
        }
        // set employee equipment ids
	public function setEquipmentIDs($userid, $accessid, $eqnumber){
                $sql = sprintf("
                        INSERT INTO	eqlink
                                        (employeeid , eqid, eqnumber)
                        VALUES		(%d,%d,%d)
                ",
                         $this->esc($userid), $this->esc($accessid), $this->esc($eqnumber)
                );
                mysqli_query($this->_Link, $sql) or $this->callError($sql);
                if (mysqli_affected_rows($this->_Link) == 1) {
            $is_updated = true;
        }
    }      
        // Add an employee
        public function adminAddEmployee($useremail, $email, $firstname, $lastname, $office){
		$sql = sprintf("
			INSERT INTO	employees
                                        (addedby, email, firstname, lastname, office, createdate)
			VALUES		('%s', '%s', '%s', '%s','%s', NOW())
		",
			$this->esc($useremail), $this->esc($email), $this->esc($firstname),
                        $this->esc($lastname), $this->esc($office)
		);
		mysqli_query($this->_Link, $sql) or $this->callError($sql);
		return mysqli_insert_id($this->_Link);
        }
        // Edit employee
        public function adminEditEmployee($userid, $email, $firstname, $lastname, $office, $startdate, $enddate, $birthdate){
		$sql = sprintf("
			UPDATE          employees
                        SET             email='%s', firstname='%s', lastname='%s', office='%s', startdate='%s', enddate='%s', birthdate='%s'
                        WHERE           (userid = %d)
		",
			$this->esc($email), $this->esc($firstname), $this->esc($lastname), $this->esc($office), $this->esc($startdate),                 
                         $this->esc($enddate), $this->esc($birthdate), $this->esc($userid)
		);
		$result = mysqli_query($this->_Link, $sql);
                if (!$result) {
            $this->callError($sql);
        }
    }
        // Edit User access
        public function adminUserAccess($userid, $accessarray){
		$sql = sprintf("
			DELETE FROM      useraccesslink
                        WHERE            userid = %d
		",
			$this->esc($userid)
		);
		$result = mysqli_query($this->_Link, $sql);
                if (!$result) {
            $this->callError($sql);
        }

        $insertstr = "";
                if(is_array($accessarray)){
                    foreach ($accessarray as $acvalue) {
                        if ($insertstr != "") {
                    $insertstr .= ",";
                }
                $insertstr .= "(" . $userid . "," . $acvalue . ")";
                    }
                }
                
                if($insertstr != ""){

                    $sql = sprintf("
                            INSERT INTO	useraccesslink
                                            (userid, accessid)
                            VALUES		%s
                    ",
                            $insertstr
                    );
                    mysqli_query($this->_Link, $sql) or $this->callError($sql);
                    return mysqli_insert_id($this->_Link);   
                
                }
            }
         // Edit User EQ
        public function adminEditEquipment($userid, $accessarray){
		$sql = sprintf("
			DELETE FROM      eqlink
                        WHERE            employeeid = %d
		",
			$this->esc($userid)
		);
		$result = mysqli_query($this->_Link, $sql);
                if (!$result) {
            $this->callError($sql);
        }

        $insertstr = "";
                if(is_array($accessarray)){
                    foreach ($accessarray as $acvalue) {
                        if ($insertstr != "") {
                    $insertstr .= ",";
                }
                $insertstr .= "(" . $userid . "," . $acvalue . ")";
                    }
                }
                
                if($insertstr != ""){

                    $sql = sprintf("
                            INSERT INTO	eqlink
                                            (employeeid, eqid)
                            VALUES		%s
                    ",
                            $insertstr
                    );
                    mysqli_query($this->_Link, $sql) or $this->callError($sql);
                    return mysqli_insert_id($this->_Link);   
                
                }
            }
            // Edit employee equipment numbers
        public function adminUpdateEqnumber($userid, $eqnumber, $eqid){
		$sql = sprintf("
			UPDATE          eqlink
                        SET             eqnumber='%s'
                        WHERE           (employeeid = %d)
                        AND             (eqid = %d)
		",
			$this->esc($eqnumber), $this->esc($userid), $this->esc($eqid)
		);
		$result = mysqli_query($this->_Link, $sql);
                if (!$result) {
            $this->callError($sql);
        }
    }
        
        // get the list of employees
        public function getEmployeeList(){
		$sql = sprintf('
			SELECT		e.userid, e.firstname, e.lastname, e.email, e.startdate, e.enddate, e.createdate, e.office, e.addedby, e.office, e.birthdate                                  
			FROM		employees e                  
                        ORDER BY        e.userid ASC
		');
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError("Result error: " . $sql);
        }
        $queryresult = array();
		while ($row = mysqli_fetch_object($result)) {
			$queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;
        }
        
        
        // get the list of employees
        public function getEmployeeById($userid){
		$sql = sprintf('
			SELECT		e.userid, e.firstname, e.lastname, e.email, e.startdate, e.enddate, e.createdate, e.office, e.addedby, e.office, e.birthdate                                 
			FROM		employees e  
                        WHERE           e.userid = %d
                        ORDER BY        e.userid ASC
		',
                     $this->esc($userid)   
                );
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError("Result error: " . $sql);
        }
        $queryresult = array();
		while ($row = mysqli_fetch_object($result)) {
			$queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;
        }
        // get the list of Verifone leads
        public function getVerifoneLeadList(){
		$sql = sprintf('
			SELECT		v.*, u.userid, u.firstname, u.lastname
			FROM		verifone_leads v 
                        LEFT OUTER JOIN users u 
                                    ON  v.userid = u.userid
                        WHERE           v.deleted != 1
                        ORDER BY        v.recid ASC
		');
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError("Result error: " . $sql);
        }
        $queryresult = array();
		while ($row = mysqli_fetch_object($result)) {
			$queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;
        }
        
        public function getradiodeiLeadList(){
		$sql = sprintf('
			SELECT		v.*, u.userid, u.firstname, u.lastname
			FROM		radiodei_leads v 
                        LEFT OUTER JOIN users u 
                                    ON  v.userid = u.userid
                        WHERE           v.deleted != 1
                        ORDER BY        v.recid ASC
		');
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError("Result error: " . $sql);
        }
        $queryresult = array();
		while ($row = mysqli_fetch_object($result)) {
			$queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;
        }
        
        public function getradiodeiLead($recordid){
		$sql = sprintf('
			SELECT          v.*, u.userid, u.firstname, u.lastname
			FROM		radiodei_leads v 
                        LEFT OUTER JOIN users u 
                                    ON  v.userid = u.userid
                        WHERE           v.deleted != 1
                        AND           recid = %d
                        ORDER BY        v.recid ASC 
		',
                     $this->esc($recordid)   
                );
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError("Result error: " . $sql);
        }
        $queryresult = array();
		while ($row = mysqli_fetch_object($result)) {
			$queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;
        }
        public function getVerifoneLead($recordid){
		$sql = sprintf('
			SELECT          v.*, u.userid, u.firstname, u.lastname
			FROM		verifone_leads v 
                        LEFT OUTER JOIN users u 
                                    ON  v.userid = u.userid
                        WHERE           v.deleted != 1
                        AND           recid = %d
                        ORDER BY        v.recid ASC 
		',
                     $this->esc($recordid)   
                );
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError("Result error: " . $sql);
        }
        $queryresult = array();
		while ($row = mysqli_fetch_object($result)) {
			$queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;
        }
         public function editVerifoneLead($company, $machine, $agreement_period, $bussinessID, $decision_maker, $title, $phone, $email, $address, $postal_code, $city, $lead_type, $lead_status, $pvm, $recordid, $lead_rating){
		$sql = sprintf("
			update      verifone_leads
                        SET         company='%s', machine='%s', agreement_period='%s', businessID='%s', decision_maker='%s', title='%s', phone='%s', email='%s', address='%s', postal_code='%s', city='%s', lead_type='%s', lead_status='%s', pvm='%s', lead_rating='%s', createdate=now()		
                        WHERE       (recid=%d)
		",
                       $this->esc($company), $this->esc($machine), $this->esc($agreement_period),$this->esc($bussinessID), $this->esc($decision_maker), $this->esc($title), $this->esc($phone), $this->esc($email), $this->esc($address), $this->esc($postal_code),$this->esc($city), $this->esc($lead_type), $this->esc($lead_status), $this->esc($pvm), $this->esc($lead_rating), $this->esc($recordid)
                    
		);
		$result = mysqli_query($this->_Link, $sql);
                if (!$result) {
            $this->callError($sql);
        }
    }  
        // Add verifone lead
        public function addVerifoneLead($company, $brand, $machine, $agreement_period, $bussinessID, $decision_maker, $title, $phone, $email, $address, $postal_code, $city, $userid, $lead_type, $lead_rating, $master){
		$sql = sprintf("
			INSERT INTO      verifone_leads
                                        (company, brand, machine, agreement_period, businessID, decision_maker, title, phone, email, address, postal_code, city, userid, lead_type, lead_rating, ismaster, createdate, pvm)
			VALUES		('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%d','%s','%s','%s', NOW(), NOW())
		",
                       $this->esc($company), $this->esc($brand), $this->esc($machine), $this->esc($agreement_period),$this->esc($bussinessID), $this->esc($decision_maker), $this->esc($title), $this->esc($phone), $this->esc($email), $this->esc($address), $this->esc($postal_code),$this->esc($city), $this->esc($userid), $this->esc($lead_type), $this->esc($lead_rating), $this->esc($master)
		);
		mysqli_query($this->_Link, $sql) or $this->callError($sql);
		return mysqli_insert_id($this->_Link);
        }
        // get the list of Verifone lead count
        public function getVerifonereportList(){
		$sql = sprintf('
			SELECT              businessID, company, seller, count(*) as Count
                        FROM                verifone_leads
                        GROUP BY            businessID       
                        
		');
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError("Result error: " . $sql);
        }
        $queryresult = array();
		while ($row = mysqli_fetch_object($result)) {
			$queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;
        }
         // get the list of Verifone lead count
        public function getVerifonereLeadByCompany($company){
		$sql = sprintf('
			SELECT              *
                        FROM                verifone_leads
                        WHERE               company = "%s"
                              
                        
		',
                    $this->esc($company)    
                );
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError("Result error: " . $sql);
        }
        $queryresult = array();
		while ($row = mysqli_fetch_object($result)) {
			$queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;
        }
        // get the list of Verifone lead count
        public function getVerifonereportListGC($begindate,$enddate){
		$sql = sprintf('
                        SELECT          v.*, u.userid, u.firstname, u.lastname
			FROM            verifone_leads v
                        LEFT OUTER JOIN users u 
                                    ON  v.userid = u.userid
                                          
                        WHERE           createdate >= "%s"
                        AND             createdate < "%s"
                        AND             v.deleted != 1
                        ORDER BY        v.lead_rating ASC
                        
		',
                     $this->esc($begindate), $this->esc($enddate)   
                );
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError("Result error: " . $sql);
        }
        $queryresult = array();
		while ($row = mysqli_fetch_object($result)) {
			$queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;
        }
         public function getVerifoneLeadCountGC($begindate,$enddate,$rating){
		$sql = sprintf('
			SELECT              COUNT(company) as companyCount
                        FROM                verifone_leads
                        WHERE createdate >= "%s"
                        AND createdate < "%s"
                        AND lead_rating = "%s"
                        
		',
                     $this->esc($begindate), $this->esc($enddate), $this->esc($rating)   
                );
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError("Result error: " . $sql);
        }
        $queryresult = array();
		while ($row = mysqli_fetch_object($result)) {
			$queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;
        }
        // get the list of Verifone lead count
        public function getVerifoneLeadCount(){
		$sql = sprintf('
			SELECT              COUNT(DISTINCT company) as companyCount
                        FROM                verifone_leads;    
                        
		');
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError("Result error: " . $sql);
        }
        $queryresult = array();
		while ($row = mysqli_fetch_object($result)) {
			$queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;
        }
        // Add verifone lead
        public function addVerifoneLead2($company, $brand, $machine, $agreement_period, $bussinessID, $decision_maker, $title, $phone, $email, $address, $postal_code, $city, $pvm, $lead_rating, $notes, $notes2, $notes3, $seller){
		$sql = sprintf("
			INSERT INTO      verifone_leads
                                        (company, brand, machine, agreement_period, businessID, decision_maker, title, phone, email, address, postal_code, city, pvm, lead_rating, notes, notes2, notes3, seller, createdate)
			VALUES		('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s',NOW())
		",
                       $this->esc($company), $this->esc($brand), $this->esc($machine), $this->esc($agreement_period),$this->esc($bussinessID), $this->esc($decision_maker), $this->esc($title), $this->esc($phone), $this->esc($email), $this->esc($address), $this->esc($postal_code),$this->esc($city), $this->esc($pvm),$this->esc($lead_rating), $this->esc($notes), $this->esc($notes2), $this->esc($notes3), $this->esc($seller)
		);
		mysqli_query($this->_Link, $sql) or $this->callError($sql);
		return mysqli_insert_id($this->_Link);
        }
        // Add notes to verifone_notes table
        public function addVerifoneNotes($recid, $notes, $repname, $photolink){
		$sql = sprintf("
			INSERT INTO      verifone_notes
			               (recid, notes,repname, photolink, notedate)
			VALUES		('%s', '%s','%s','%s', NOW())
		",
                       $this->esc($recid), $this->esc($notes), $this->esc($repname), $this->esc($photolink)                        
		);
		mysqli_query($this->_Link, $sql) or $this->callError($sql);
		return mysqli_insert_id($this->_Link);
        }
        // Add notes to verifone_notes table
        public function addVerifoneNotesWithRecID($recid, $notes, $repname, $photolink){
		$sql = sprintf("
			INSERT INTO      verifone_notes
                                        (notes, repname, photolink, recid)
			VALUES		('%s', '%s','%s', %d)                      
		",
                       $this->esc($notes), $this->esc($repname), $this->esc($photolink), $this->esc($recid)                        
		);
		mysqli_query($this->_Link, $sql) or $this->callError($sql);
		return mysqli_insert_id($this->_Link);
        }
        public function getVerifoneNotes($recordid){
		$sql = sprintf('
			SELECT		*                                 
			FROM		verifone_notes  
                        WHERE           recid = %s
                        ORDER BY        notesid DESC
		',
                       $this->esc($recordid)                        
		);
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError($sql);
        }
        $queryresult = array();
		while ($row = mysqli_fetch_object($result)) {
                    $queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;        
                }
        public function deleteVerifoneLead($recid){
		$sql = sprintf("
			UPDATE          verifone_leads
                        SET             deleted = 1
                        WHERE           (recid = %d)
		",
                       $this->esc($recid)                
		);
		mysqli_query($this->_Link, $sql) or $this->callError($sql);
		return mysqli_insert_id($this->_Link);
        }
        public function deleteVerfoneNotesPerID($recid){
		$sql = sprintf("
			UPDATE          verifone_notes
                        SET             deleted = 1
                        WHERE           (recid = %d)
		",
                       $this->esc($recid)                
		);
		mysqli_query($this->_Link, $sql) or $this->callError($sql);
		return mysqli_insert_id($this->_Link);
        }
        
        // Add verifone lead
        public function addradiodeiLead($Puhelinnumero, $Ytunnus, $Yritys, $Etunimi, $Sukunimi, $Titteli, $Osoite, $Kaupunki, $Email, $Vaihtoehtoinen_puhelinnumero, $Lisätietoja){
		$sql = sprintf("
			INSERT INTO      radiodei_leads
                                        (Puhelinnumero, Ytunnus, Yritys, Etunimi, Sukunimi, Titteli, Osoite, Kaupunki, Sähköposti, Vaihtoehtoinen_puhelinnumero, Lisätietoja, createdate)
			VALUES		('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s',NOW())
		",
                       $this->esc($Puhelinnumero), $this->esc($Ytunnus), $this->esc($Yritys), $this->esc($Etunimi),$this->esc($Sukunimi), $this->esc($Titteli), $this->esc($Osoite), $this->esc($Kaupunki), $this->esc($Email), $this->esc($Vaihtoehtoinen_puhelinnumero), $this->esc($Lisätietoja)
		);
		mysqli_query($this->_Link, $sql) or $this->callError($sql);
		return mysqli_insert_id($this->_Link);
        }
        public function getRadioDeiNotes($recordid){
		$sql = sprintf('
			SELECT		*                                 
			FROM		radiodei_notes  
                        WHERE           recid = %s
                        ORDER BY        notesid DESC
		',
                       $this->esc($recordid)                        
		);
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError($sql);
        }
        $queryresult = array();
		while ($row = mysqli_fetch_object($result)) {
                    $queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;        
                }
                
        public function editRadioDeiLead($agreement_period, $Uid, $recordid, $seller, $donation){
            $agreement = "";
           
                if($agreement_period == "Ei"){
                    $agreement = 0;               
                }elseif($agreement_period == "One Time Donation"){
                    $agreement = 1;               
                }else{
                    $agreement = 2;         
                }  
                
		$sql = sprintf("
			update      radiodei_leads
                        SET         agreement_period='%s', seller='%s', donation_amount='%s', userid='%d'	
                        WHERE       (recid=%d)
		",
                       $this->esc($agreement), $this->esc($seller), $this->esc($donation),$this->esc($Uid), $this->esc($recordid)
                    
		);
		$result = mysqli_query($this->_Link, $sql);
                if (!$result) {
            $this->callError($sql);
        }
    }
         // Add notes to radiodei_notes table
        public function addRadioDeiNotes($recid, $notes, $repname, $photolink){
		$sql = sprintf("
			INSERT INTO      radiodei_notes
			               (recid, notes,repname, photolink, notedate)
			VALUES		('%s', '%s','%s','%s', NOW())
		",
                       $this->esc($recid), $this->esc($notes), $this->esc($repname), $this->esc($photolink)                        
		);
		mysqli_query($this->_Link, $sql) or $this->callError($sql);
		return mysqli_insert_id($this->_Link);
        }
        public function editIRRTVLead($agreement_period, $Uid, $recordid, $seller, $donation){
            $agreement = "";
           
                if($agreement_period == "Ei"){
                    $agreement = 0;               
                }elseif($agreement_period == "One Time Donation"){
                    $agreement = 1;               
                }else{
                    $agreement = 2;         
                }  
                
		$sql = sprintf("
			update      irrtv_leads
                        SET         agreement_period='%s', seller='%d', donation_amount='%s', userid='%d'	
                        WHERE       (recid=%d)
		",
                        $this->esc($agreement), $this->esc($Uid), $this->esc($donation), $this->esc($Uid), $this->esc($recordid)
                    
		);
		$result = mysqli_query($this->_Link, $sql);
                if (!$result) {
            $this->callError($sql);
        }
    }
         // Generic add function 
        public function addTime($value1, $value2, $value3,  $value4, $value5, $value6){
		$sql = sprintf("
			INSERT INTO      employee_time
                                        (userid, idate, start_time, end_time, total_time, project, username, createdate)
			VALUES		('%s','%s','%s','%s', TIMEDIFF('%s','%s'),'%s','%s',NOW())
		",
                       $this->esc($value1), $this->esc($value2), $this->esc($value3), $this->esc($value4),$this->esc($value4), $this->esc($value3),$this->esc($value5), $this->esc($value6)
		);
		mysqli_query($this->_Link, $sql) or $this->callError($sql);
		return mysqli_insert_id($this->_Link);
        }
        // Add notes us info to database
        public function editTime($value1, $value2){
		$sql = sprintf("
			UPDATE          employee_time	              
			SET             username='%s'
                        WHERE           recid = '%s'
		",
                       $this->esc($value1),$this->esc($value2)             
		);
		mysqli_query($this->_Link, $sql) or $this->callError($sql);
		return mysqli_insert_id($this->_Link);
        }
        public function getAllEmployeeTimes(){
		$sql = sprintf('
			SELECT		*                                 
			FROM		employee_time  
       		'                                          
		);
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError($sql);
        }
        $queryresult = array();
		while ($row = mysqli_fetch_object($result)) {
                    $queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;        
                }
        
        public function getEmployeeTimes($userid){
            $sql = sprintf('
                    SELECT		*                                 
                    FROM		employee_time 
                    WHERE           userid = %s

            ',
                   $this->esc($userid)                        
            );
            $result = mysqli_query($this->_Link, $sql);
            if (!$result) {
            $this->callError($sql);
        }
        $queryresult = array();
            while ($row = mysqli_fetch_object($result)) {
                $queryresult[] = $row;
            }
            mysqli_free_result($result);
            return $queryresult;        
        }
        public function getProjcetTotalTime($talbe, $projectname){
		$sql = sprintf('
			SELECT  SEC_TO_TIME( SUM( TIME_TO_SEC( `total_time` ) ) ) AS totaltime , project
                        FROM %s
                        WHERE project = "%s"
		',
                        $this->esc($talbe), $this->esc($projectname)
                );
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError("Result error: " . $sql);
        }
        $queryresult = array();
		while ($row = mysqli_fetch_object($result)) {
			$queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;
        }
        public function getProjcetTimeList($talbe){
		$sql = sprintf('
			SELECT  *
                        FROM %s
		',
                        $this->esc($talbe)
                );
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError("Result error: " . $sql);
        }
        $queryresult = array();
		while ($row = mysqli_fetch_object($result)) {
			$queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;
        }
        public function getPersonlTotalTime($talbe, $userid){
		$sql = sprintf('
			SELECT  SEC_TO_TIME( SUM( TIME_TO_SEC( `total_time` ) ) ) AS totaltime, project
                        FROM    %s
                        WHERE   userid=%s
		',
                        $this->esc($talbe), $this->esc($userid)
                );
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError("Result error: " . $sql);
        }
        $queryresult = array();
		while ($row = mysqli_fetch_object($result)) {
			$queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;
        }
        
        // get the list of Verifone leads
        public function getMashLeadList(){
		$sql = sprintf('
			SELECT		v.*, u.userid, u.firstname, u.lastname
			FROM		mash_leads v 
                        LEFT OUTER JOIN users u 
                                    ON  v.userid = u.userid
                        WHERE           v.deleted != 1
                        ORDER BY        v.recid ASC
		');
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError("Result error: " . $sql);
        }
        $queryresult = array();
		while ($row = mysqli_fetch_object($result)) {
			$queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;
        }
        public function getMashLeadListByID($recordid){
		$sql = sprintf('
			SELECT          v.*, u.userid, u.firstname, u.lastname
			FROM		mash_leads v 
                        LEFT OUTER JOIN users u 
                                    ON  v.userid = u.userid
                        WHERE           v.deleted != 1
                        AND           recid = %d
                        ORDER BY        v.recid ASC 
		',
                     $this->esc($recordid)   
                );
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError("Result error: " . $sql);
        }
        $queryresult = array();
		while ($row = mysqli_fetch_object($result)) {
			$queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;
        }
        
        public function editMashLead($company, $toimiala, $lead_type, $revenue, $recordid){
		$sql = sprintf("
			update      mash_leads
                        SET         company_name='%s', toimiala='%s', lead_type='%s', revenue='%s', createdate=now()		
                        WHERE       (recid=%d)
		",
                       $this->esc($company), $this->esc($toimiala),$this->esc($lead_type),$this->esc($revenue), $this->esc($recordid)
                    
		);
		$result = mysqli_query($this->_Link, $sql);
                if (!$result) {
            $this->callError($sql);
        }
    }
// Add verifone lead
        public function addMashLeadxlsx($company, $toimiala, $lead_type, $revenue, $notes, $seller){
		$sql = sprintf("
			INSERT INTO      mash_leads
                                        (company_name, toimiala, lead_type, revenue, notes, seller, createdate)
			VALUES		('%s','%s','%s','%s','%s','%s',NOW())
		",
                       $this->esc($company), $this->esc($toimiala), $this->esc($lead_type), $this->esc($revenue),$this->esc($notes), $this->esc($seller)
		);
		mysqli_query($this->_Link, $sql) or $this->callError($sql);
		return mysqli_insert_id($this->_Link);
        }
        
        // Add verifone lead
         public function addMashLead2($company, $toimiala, $lead_type, $revenue, $companyid, $repname){
		$sql = sprintf("
			INSERT INTO      mash_leads
			               (company_name, toimiala, lead_type, revenue, companyID, seller, createdate)
			VALUES		('%s', '%s', '%s', '%s', '%s', '%s', NOW())
		",
                       $this->esc($company), $this->esc($toimiala), $this->esc($lead_type), $this->esc($revenue), $this->esc($companyid), $this->esc($repname)                      
		);
		mysqli_query($this->_Link, $sql) or $this->callError($sql);
		return mysqli_insert_id($this->_Link);
        }    
// Add notes to mashlead_notes table
        public function addMashNotesWithRecID($recid, $notes, $repname, $photolink){
		$sql = sprintf("
			INSERT INTO      mashlead_notes
                                        (notes, repname, photolink, recid)
			VALUES		('%s', '%s','%s', %d)                      
		",
                       $this->esc($notes), $this->esc($repname), $this->esc($photolink), $this->esc($recid)                        
		);
		mysqli_query($this->_Link, $sql) or $this->callError($sql);
		return mysqli_insert_id($this->_Link);
        }        
        public function getMashleadNotes($recordid){
		$sql = sprintf('
			SELECT		*                                 
			FROM		mashlead_notes  
                        WHERE           recid = %s
                        ORDER BY        notesid DESC
		',
                       $this->esc($recordid)                        
		);
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError($sql);
        }
        $queryresult = array();
		while ($row = mysqli_fetch_object($result)) {
                    $queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;        
                }
        // Add notes to mashlead_notes table
        public function addMashleadNotes($recid, $notes, $repname, $photolink){
		$sql = sprintf("
			INSERT INTO      mashlead_notes
			               (recid, notes,repname, photolink, notedate)
			VALUES		('%s', '%s','%s','%s', NOW())
		",
                       $this->esc($recid), $this->esc($notes), $this->esc($repname), $this->esc($photolink)                        
		);
		mysqli_query($this->_Link, $sql) or $this->callError($sql);
		return mysqli_insert_id($this->_Link);
        }
        // get the list of Verifone leads
        public function getLeadList($table){
		$sql = sprintf('
			SELECT		v.*, u.userid, u.firstname, u.lastname
			FROM		%s v 
                        LEFT OUTER JOIN users u 
                                    ON  v.userid = u.userid
                        WHERE           v.deleted != 1
                        ORDER BY        v.recid ASC
		',
                        $this->esc($table)
                );
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError("Result error: " . $sql);
        }
        $queryresult = array();
		while ($row = mysqli_fetch_object($result)) {
			$queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;
        }
        public function getLeadByID($table, $recordid){
		$sql = sprintf('
			SELECT          v.*, u.userid, u.firstname, u.lastname
			FROM		%s v 
                        LEFT OUTER JOIN users u 
                                    ON  v.userid = u.userid
                        WHERE           v.deleted != 1
                        AND           recid = %d
                        ORDER BY        v.recid ASC 
		',
                     $this->esc($table), $this->esc($recordid)   
                );
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError("Result error: " . $sql);
        }
        $queryresult = array();
		while ($row = mysqli_fetch_object($result)) {
			$queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;
        }
         // Add notes to verifone_notes table
        public function addNotes($table, $recid, $notes, $repname, $photolink){
		$sql = sprintf("
			INSERT INTO      %s
			               (recid, notes,repname, photolink, notedate)
			VALUES		('%s', '%s','%s','%s', NOW())
		",
                       $this->esc($table), $this->esc($recid), $this->esc($notes), $this->esc($repname), $this->esc($photolink)                        
		);
		mysqli_query($this->_Link, $sql) or $this->callError($sql);
		return mysqli_insert_id($this->_Link);
        }
        public function getNotes($table, $recordid){
		$sql = sprintf('
			SELECT		*                                 
			FROM		%s 
                        WHERE           recid = %s
                        ORDER BY        notesid DESC
		',
                       $this->esc($table), $this->esc($recordid)                        
		);
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError($sql);
        }
        $queryresult = array();
		while ($row = mysqli_fetch_object($result)) {
                    $queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;        
        }
        public function editMashEngagement($table, $company, $bussinessID, $decision_maker, $phone, $email, $recordid, $visible, $materials, $communications, $campaigns, $consultation, $transactions, $returned){
		$sql = sprintf("
			update      %s
                        SET         company='%s', businessID='%s', decision_maker='%s', phone='%s', email='%s', visable='%s', materials='%s', communications='%s', campaigns='%s', consultations='%s', transactions='%s', terminal_return='%s', changedate=now()		
                        WHERE       (recid=%d)
		",
                       $this->esc($table),$this->esc($company), $this->esc($bussinessID), $this->esc($decision_maker), $this->esc($phone), $this->esc($email), $this->esc($visible), $this->esc($materials),$this->esc($communications), $this->esc($campaigns), $this->esc($consultation), $this->esc($transactions), $this->esc($returned), $this->esc($recordid)
                    
		);
		$result = mysqli_query($this->_Link, $sql);
                if (!$result) {
            $this->callError($sql);
        }
    }  
        
        public function getProjectResults($tablename, $search_column, $base_value){
		$sql = sprintf('
			SELECT		*                                 
			FROM		%s 
                        WHERE           %s != "%s"
		',
                       $this->esc($tablename), $this->esc($search_column), $this->esc($base_value)                        
		);
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError($sql);
        }
        $queryresult = array();
		while ($row = mysqli_fetch_object($result)) {
                    $queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;        
                }
        // get the eq link list
        public function geteqlinkList(){
		$sql = sprintf('
			SELECT		e.eqid, e.employeeid, e.eqnumber                                 
			FROM		eqlink e                 
                        ORDER BY        e.employeeid ASC
		');
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError("Result error: " . $sql);
        }
        $queryresult = array();
		while ($row = mysqli_fetch_object($result)) {
			$queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;
        }
        
        public function geteqlinkList2($userid){
		$sql = sprintf("
                        SELECT      a.eqid, a.item, ul.employeeid, ul.eqnumber
                        FROM        equipment a
                        LEFT OUTER JOIN eqlink ul
                                   ON ul.employeeid = %d AND ul.eqid = a.eqid
                        ORDER BY    a.eqid ASC
		",
                        $this->esc($userid)
                );
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError($sql);
        }
        $queryresult = array();
		while ($row = mysqli_fetch_object($result)) {
                    $queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;            
        }
        // get invoice list by employee id
        public function getInvoiceByEmpId($employeeId){
		$sql = sprintf('
			SELECT		*                                 
			FROM		employee_invoices 
                        WHERE           userid = %d
                        AND             deleted !=1
                        ORDER BY        recid ASC
		',
                    $this->esc($employeeId)
                );
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError("Result error: " . $sql);
        }
        $queryresult = array();
		while ($row = mysqli_fetch_object($result)) {
			$queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;
        }
        // get invoice list by employee id
        public function getEmployeeInvoiceByEmpIdAndYear($employeeId, $year){
		$sql = sprintf('
			SELECT		*                                 
			FROM		employee_invoices
                        WHERE           YEAR(invoice_date)=%s
                        AND              userid = %d
                        AND             deleted != 1
                        
		',
                    $this->esc($year), $this->esc($employeeId)
                );
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError("Result error: " . $sql);
        }
        $queryresult = array();
		while ($row = mysqli_fetch_object($result)) {
			$queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;
        }
        // get invoice list by employee id
        public function getAllEmployeeInvoices(){
		$sql = sprintf('
			SELECT		E.recid, E.userid, E.invoice_date, E.invoice_amount, E.paid_date, E.createdate,
                                        E.notes, E.deleted, U.userid, U.firstname, U.lastname
			FROM		employee_invoices E
                        LEFT OUTER JOIN users U
                                    ON  E.userid = U.userid
                        WHERE           deleted !=1                     
                        ORDER BY        recid ASC
		'
                );
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError("Result error: " . $sql);
        }
        $queryresult = array();
		while ($row = mysqli_fetch_object($result)) {
			$queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;
        }
        // Add employee invoices
        public function addinvoice($userid, $invoicedate, $amount, $notes){
		$sql = sprintf("
			INSERT INTO      employee_invoices
			               (userid, invoice_date, invoice_amount, notes, createdate)
			VALUES		('%d', '%s', '%s', '%s', NOW())
		",
                       $this->esc($userid), $this->esc($invoicedate), $this->esc($amount), $this->esc($notes)                     
		);
		mysqli_query($this->_Link, $sql) or $this->callError($sql);
		return mysqli_insert_id($this->_Link);
        }
         public function deleteinvoice($recid){
		$sql = sprintf("
			UPDATE          employee_invoices
                        SET             deleted = 1
                        WHERE           (recid = %d)
		",
                       $this->esc($recid)                
		);
		mysqli_query($this->_Link, $sql) or $this->callError($sql);
		return mysqli_insert_id($this->_Link);
        }
        
        // get swedish list 1
        public function getSweden1(){
		$sql = sprintf('
			SELECT		*                                 
			FROM		swedishwnum                 
                        ORDER BY        recid ASC
		');
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError("Result error: " . $sql);
        }
        $queryresult = array();
		while ($row = mysqli_fetch_object($result)) {
			$queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;
        }
        // get swedish list 2
        public function getSweden2(){
		$sql = sprintf('
			SELECT		*                                 
			FROM		swedish2                 
                        ORDER BY        recid ASC
		');
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError("Result error: " . $sql);
        }
        $queryresult = array();
		while ($row = mysqli_fetch_object($result)) {
			$queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;
        }
        // get swedish list 3
        public function getSweden3(){
		$sql = sprintf('
			SELECT		*                                 
			FROM		swedish3                 
                        ORDER BY        recid ASC
		');
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError("Result error: " . $sql);
        }
        $queryresult = array();
		while ($row = mysqli_fetch_object($result)) {
			$queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;
        }
        public function removeSwedish5Rec($recid){
		$sql = sprintf("
			DELETE FROM      swedish5
                        WHERE            recid = %d
		",
			$this->esc($recid)
		);
		$result = mysqli_query($this->_Link, $sql);
                if (!$result) {
            $this->callError($sql);
        }
    }
        // Add contact us info to database
        public function addSweden3($recid, $yritys, $ytunnus, $osoite, $postinumero, $kaupunki, $puhelin, $teollisuus, $industry, $Auditor){
		$sql = sprintf("
			INSERT INTO      swedish4
			               (recid, yritys, ytunnus, osoite, postinumero, kaupunki, puhelin, teollisuus, industry, Auditor)
			VALUES		('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		",
                       $this->esc($recid), $this->esc($yritys), $this->esc($ytunnus), $this->esc($osoite), $this->esc($postinumero), $this->esc($kaupunki), $this->esc($puhelin), $this->esc($teollisuus), $this->esc($industry), $this->esc($Auditor)
		);
		mysqli_query($this->_Link, $sql) or $this->callError($sql);
		return mysqli_insert_id($this->_Link);
        }
         
        // Add contact us info to database
        public function addStoreMash($seller, $campaignid, $company, $ytunnus, $firstname, $lastname, $email, $phonenumber, $street, $webpage, $city, $zip, $deal, $notes ){
		$sql = sprintf("
			INSERT INTO      mash
			               (Seller, Campaignid, company, ytunnus, firstname, lastname, email, phonenumber, website, street, city, zip, deal, notes, createdate)
			VALUES		('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', NOW() )
		",
                       $this->esc($seller), $this->esc($campaignid), $this->esc($company), $this->esc($ytunnus), $this->esc($firstname), $this->esc($lastname), $this->esc($email), $this->esc($phonenumber), $this->esc($webpage), $this->esc($street), $this->esc($city), $this->esc($zip), $this->esc($deal), $this->esc($notes)                        
		);
		mysqli_query($this->_Link, $sql) or $this->callError($sql);
		return mysqli_insert_id($this->_Link);
        }
        
        // Add contact us info to database
        public function addMashLead($seller, $campaignid, $company, $secondagent){
		$sql = sprintf("
			INSERT INTO      mash
			               (Seller, Campaignid, company, seller2, createdate)
			VALUES		('%s', '%s', '%s', '%s', NOW())
		",
                       $this->esc($seller), $this->esc($campaignid), $this->esc($company), $this->esc($secondagent)                     
		);
		mysqli_query($this->_Link, $sql) or $this->callError($sql);
		return mysqli_insert_id($this->_Link);
        }    
        // Add notes us info to database
        public function addMashNotes($mashid, $notes){
		$sql = sprintf("
			INSERT INTO      mash_notes
			               (mashid, notes, notedate)
			VALUES		('%s', '%s', NOW())
		",
                       $this->esc($mashid), $this->esc($notes)                        
		);
		mysqli_query($this->_Link, $sql) or $this->callError($sql);
		return mysqli_insert_id($this->_Link);
        }
        // Add notes us info to database
        // Add notes us info to database
        public function addMashSalesPersonInfo($mashid, $name, $email, $phonenumber, $agreement){
		$sql = sprintf("
			INSERT INTO      mash_salespersoninfo
			               (mashid, name, email, phonenumber, agreement, createdate )
			VALUES		('%d', '%s', '%s', '%s', '%s', NOW())
		",
                       $this->esc($mashid), $this->esc($name), $this->esc($email), $this->esc($phonenumber), $this->esc($agreement)                        
		);
		mysqli_query($this->_Link, $sql) or $this->callError($sql);
		return mysqli_insert_id($this->_Link);
        }
        // Add notes us info to database
        public function addMashContractPersonInfo($mashid, $email, $phonenumber, $agreement){
		$sql = sprintf("
			INSERT INTO      mash_contractpersoninfo
			               (mashid, email, phonenumber, agreement, createdate )
			VALUES		('%d', '%s', '%s', '%s', NOW())
		",
                       $this->esc($mashid), $this->esc($email), $this->esc($phonenumber), $this->esc($agreement)                       
		);
		mysqli_query($this->_Link, $sql) or $this->callError($sql);
		return mysqli_insert_id($this->_Link);
        }
        
        // Add notes us info to database
        public function addMashContact($mashid, $name, $email, $phonenumber){
		$sql = sprintf("
			INSERT INTO      mash_contractpersoninfo
			               (mashid, name, email, phonenumber, createdate )
			VALUES		('%d', '%s', '%s', '%s', NOW())
		",
                       $this->esc($mashid),$this->esc($name), $this->esc($email), $this->esc($phonenumber)               
		);
		mysqli_query($this->_Link, $sql) or $this->callError($sql);
		return mysqli_insert_id($this->_Link);
        }
        
        // Add notes us info to database
        public function addMashMachineInfo($mashid, $verifone, $otherpayterminal, $integration, $intergrationtype, $otherterminalintegrate, $othertypeintegration){
		$sql = sprintf("
			INSERT INTO      mash_machineinfo
			               (mashid, verifone, otherpayterminal, integration, integrationtype, otherterminalintegrate, othertypeintegration, createdate )
			VALUES		('%d', '%s', '%s', '%s', '%s', '%s', '%s', NOW())
		",
                       $this->esc($mashid), $this->esc($verifone), $this->esc($otherpayterminal), $this->esc($integration), $this->esc($intergrationtype), $this->esc($otherterminalintegrate), $this->esc($othertypeintegration)                       
		);
		mysqli_query($this->_Link, $sql) or $this->callError($sql);
		return mysqli_insert_id($this->_Link);
        }
        
        // Get campaign list
	public function getCampaignList(){
		$sql = sprintf("
			SELECT		C.campaignid, C.name, C.month, C.year
			FROM		mash_campaigns C                       			
		"		
		);
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError("Result error: " . $sql);
        }
        $queryresult = array();
		while ($row = mysqli_fetch_object($result)) {
			$queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;
	}
        
        // Get Mash store list
	public function getMashStores(){
		$sql = sprintf("
			SELECT		m.id, m.Seller, m.campaignid, m.company, m.ytunnus, m.firstname, m.lastname, m.email, m.phonenumber,
                                        m.street, m.street, m.city, m.deal, m.createdate
			FROM		mash m			
		"
		);
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError("Result error: " . $sql);
        }
        $queryresult = array();
		while ($row = mysqli_fetch_object($result)) {
                    $queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;
	}
         // Get Mash store detail
	public function getMashStoreDetail($mashid){
		$sql = sprintf("
			SELECT		m.id, m.Seller, m.campaignid, m.company, m.ytunnus, m.firstname, m.lastname, m.email, m.phonenumber,
                                        m.street, m.street, m.city, m.zip, m.leadphaseid, m.deal, m.createdate
			FROM		mash m
                        WHERE           id=%d			
		",
                        $this->esc($mashid)
		);
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError($sql);
        }
        $queryresult  = mysqli_fetch_object($result);
		mysqli_free_result($result);
                                    
		return $queryresult;
	}
        
        // Get Mash machine info
	public function getMashMachine($mashid){
		$sql = sprintf("
			SELECT		m.machineid, m.mashid, m.verifone, m.otherpayterminal, m.integration, m.integrationtype, m.otherterminalintegrate,
                                        m.othertypeintegration
			FROM		mash_machineinfo m
			WHERE           mashid=%d			
		",
                        $this->esc($mashid)
		);
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError($sql);
        }
        $queryresult  = mysqli_fetch_object($result);
		mysqli_free_result($result);
                                    
		return $queryresult;
	}
        
        // Get Mash store list
	public function getMashnotes($mashid){
		$sql = sprintf("
			SELECT		n.notesid, n.notes, n.mashid, n.notedate
			FROM		mash_notes n
			WHERE           mashid=%d			
		",
                        $this->esc($mashid)
		);
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError("Result error: " . $sql);
        }
        $queryresult = array();
		while ($row = mysqli_fetch_object($result)) {
                    $queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;
	}
        
        // Get Mash contract persons info
	public function getMashContractPerson($mashid){
		$sql = sprintf("
			SELECT		c.contractid, c.mashid, c.createdate, c.name, c.email, c.phonenumber, c.agreement
			FROM		mash_contractpersoninfo c
                        WHERE           mashid=%d			
		",
                        $this->esc($mashid)
		);
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError($sql);
        }
        $queryresult  = mysqli_fetch_object($result);
		mysqli_free_result($result);
                                    
		return $queryresult;
	}
        
        // Get Mash sales persons info
	public function getMashSalesPerson($mashid){
		$sql = sprintf("
			SELECT		s.storeid, s.mashid, s.createdate, s.name, s.email, s.phonenumber, s.agreement                       
			FROM		mash_salespersoninfo s
			WHERE           mashid=%d			
		",
                        $this->esc($mashid)
		);
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError($sql);
        }
        $queryresult  = mysqli_fetch_object($result);
		mysqli_free_result($result);
                                    
		return $queryresult;
	}
        
         // Get Mash sales persons info
	public function getMashLeadPhases(){
		$sql = sprintf("
			SELECT		l.leadphaseid, l.name                     
			FROM		mash_leadphases l			
		"
		);
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError("Result error: " . $sql);
        }
        $queryresult = array();
		while ($row = mysqli_fetch_object($result)) {
                    $queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;
	}
        
        // Get Mash campaigns list
	public function getMashcampaigns(){
		$sql = sprintf("
			SELECT		m.name, m.campaignid, m.month, m.year                                    
			FROM		mash_campaigns m			
		"
		);
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError("Result error: " . $sql);
        }
        $queryresult = array();
		while ($row = mysqli_fetch_object($result)) {
                    $queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;
	}
        
        // Add csv us info to database
        public function addMashCsvFile($ytunnus, $toimipaikan_nimi, $puhelin, $käyntiosoite, $postinumero, $postitoimipaikka, $postitusosoitteen_katuosoite, $kotisivu, $toimiala, $etunimi, $sukunimi, $titteli, $kontaktin_gsm_numero, $kontaktin_sähköpostiosoite, $kontaktin_lankanumero, $kontakti_päivitetty, $liikevaihtoluokka){
		$sql = sprintf("
			INSERT INTO      mash_ecom
			               (ytunnus, toimipaikan_nimi, puhelin, käyntiosoite, postinumero, postitoimipaikka, postitusosoitteen_katuosoite, kotisivu, toimiala, etunimi, sukunimi, titteli, kontaktin_gsm_numero, kontaktin_sähköpostiosoite, kontaktin_lankanumero, kontakti_päivitetty, liikevaihtoluokka, createdate )
			VALUES		('%s', '%s', '%s', '%s', '%d', '%s', '%s','%s', '%s', '%s', '%s', '%s', '%d', '%s','%d', '%s', '%s', NOW())
		",
                       $this->esc($ytunnus), $this->esc($toimipaikan_nimi), $this->esc($puhelin), $this->esc($käyntiosoite), $this->esc($postinumero), $this->esc($postitoimipaikka), $this->esc($postitusosoitteen_katuosoite), $this->esc($kotisivu), $this->esc($toimiala), $this->esc($etunimi), $this->esc($sukunimi), $this->esc($titteli), $this->esc($kontaktin_gsm_numero), $this->esc($kontaktin_sähköpostiosoite), $this->esc($kontaktin_lankanumero), $this->esc($kontakti_päivitetty), $this->esc($liikevaihtoluokka)                     
		);
		mysqli_query($this->_Link, $sql) or $this->callError($sql);
		return mysqli_insert_id($this->_Link);
        }
         // Add csv us info to database
        public function addMashMerchantFile($Puhelinnumero, $ytunnus, $yritys, $Etunimi, $Sukunimi, $Osoite, $Kaupunki, $Sähköposti, $Soittoyrityksiä, $Viimeisin_soitto, $Käytetty, $Tulos, $Tuloksen_päiväys, $Kommentit, $sn, $Merchantid, $Inactive_Terminal,  $Number_of_terminals, $Postinumero, $Activation_date, $ApplicationDate, $number_of_days){
		$sql = sprintf("
			INSERT INTO      mash_merchantcallbacks
			               (Puhelinnumero, ytunnus, yritys, Etunimi, Sukunimi, Osoite, Kaupunki, Sähköposti, Soittoyrityksiä, Viimeisin_soitto, Käytetty, Tulos, Tuloksen_päiväys, Kommentit, sn, Merchantid, Inactive_Terminal, Number_of_terminals, Postinumero, Activation_date, ApplicationDate, number_of_days )
			VALUES		('%s', '%s', '%s', '%s', '%s', '%s', '%s','%s', '%d', '%s', '%s', '%s', '%s', '%s','%d', '%d', '%s', '%d', '%d', '%s', '%s', '%d')
		",
                       $this->esc($Puhelinnumero), $this->esc($ytunnus), $this->esc($yritys), $this->esc($Etunimi), $this->esc($Sukunimi), $this->esc($Osoite), $this->esc($Kaupunki), $this->esc($Sähköposti), $this->esc($Soittoyrityksiä), $this->esc($Viimeisin_soitto), $this->esc($Käytetty), $this->esc($Tulos), $this->esc($Tuloksen_päiväys), $this->esc($Kommentit), $this->esc($sn), $this->esc($Merchantid), $this->esc($Inactive_Terminal), $this->esc($Number_of_terminals), $this->esc($Postinumero), $this->esc($Activation_date), $this->esc($ApplicationDate), $this->esc($number_of_days)                      
		);
		mysqli_query($this->_Link, $sql) or $this->callError($sql);
		return mysqli_insert_id($this->_Link);
        }
         
        // Get Mash Ecom store list
	public function getMashEcomStores(){
		$sql = sprintf("
			SELECT          E.ytunnus, E.toimipaikan_nimi, E.puhelin, E.käyntiosoite, E.postinumero, E.postitoimipaikka, E.postitusosoitteen_katuosoite, E.kotisivu, 
                                        E.toimiala, E.etunimi, E.sukunimi, E.titteli, E.kontaktin_gsm_numero, E.kontaktin_sähköpostiosoite, E.kontaktin_lankanumero, E.kontakti_päivitetty, 
                                        E.liikevaihtoluokka, E.createdate		
			FROM		mash_ecom E			
		"
		);
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError("Result error: " . $sql);
        }
        $queryresult = array();
		while ($row = mysqli_fetch_object($result)) {
                    $queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;
	}
        
        // Get Mash callback merchant list
	public function getMashcallbackmerchant(){
		$sql = sprintf("
			SELECT         m.recid, m.Puhelinnumero, m.ytunnus, m.yritys, m.Etunimi, m.Sukunimi, m.Osoite, m.Kaupunki, m.Sähköposti, m.Soittoyrityksiä, m.Viimeisin_soitto, m.Käytetty, 
                                       m.Tulos, m.Tuloksen_päiväys, m.Kommentit, m.sn, m.Merchantid, m.Inactive_Terminal, m.Number_of_terminals, m.Postinumero, m.Activation_date, m.ApplicationDate, 
                                       m.number_of_days		
			FROM		mash_merchantcallbacks	m		
		"
		);
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError("Result error: " . $sql);
        }
        $queryresult = array();
		while ($row = mysqli_fetch_object($result)) {
                    $queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;
	}
         // get call back merchant by id
        public function getCallBackMerchantById($recid){
		$sql = sprintf('
			SELECT         m.recid, m.Puhelinnumero, m.ytunnus, m.yritys, m.Etunimi, m.Sukunimi, m.Osoite, m.Kaupunki, m.Sähköposti AS Sahkoposti, m.Soittoyrityksiä AS Soittoyrityksia, m.Viimeisin_soitto, m.Käytetty AS Kaytetty, 
                                       m.Tulos, m.Tuloksen_päiväys AS Tuloksen_paivays, m.Kommentit, m.sn, m.Merchantid, m.Inactive_Terminal, m.Number_of_terminals, m.Postinumero, m.Activation_date, m.ApplicationDate, 
                                       m.number_of_days		
			FROM		mash_merchantcallbacks	m 
                        WHERE           m.recid = %d
                        ORDER BY        m.recid ASC
		',
                     $this->esc($recid)   
                );
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError("Result error: " . $sql);
        }
        $queryresult = array();
		while ($row = mysqli_fetch_object($result)) {
			$queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;
        }
        // get call back merchant by id
        public function getCallBackMerchantInfoByName($ytunnus){
		$sql = sprintf('
			SELECT         m.recid, m.yritys, m.ytunnus, m.etunimi, m.sukunimi, m.puhelin, m.Agentinnimi, m.Tila, m.Commenti, m.Soitonlopputulos, m.Soitonlopputuloslisätieto AS Soitonlopputuloslisatieto, 
                                       m.Soittoaika, m.Kellonaika, m.Duration , Talktime 
                                      		
			FROM		mash_merchantcallbackinfo	m 
                        WHERE           m.ytunnus = %d
                        ORDER BY        m.recid ASC
		',
                     $this->esc($ytunnus)   
                );
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError("Result error: " . $sql);
        }
        $queryresult = array();
		while ($row = mysqli_fetch_object($result)) {
			$queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;
        }
        // get call back merchant by id
        public function getCallBackMerchantEndReasonCount(){
		$sql = sprintf('
			SELECT      DISTINCT(Tulos) as title, count(Tulos) AS count 
                        FROM        mash_merchantcallbacks 
                        GROUP BY    Tulos 
                        HAVING      count > 1  
                        
		'
                       
                );
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError("Result error: " . $sql);
        }
        $queryresult = array();
		while ($row = mysqli_fetch_object($result)) {
			$queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;
        }
        
         // get sum of outgoing payments
        public function getOutgoingBanking(){
		$sql = sprintf('
			SELECT      SUM(euro) AS outgoing
                        FROM        allbank 
                        WHERE       transactions        LIKE "Self service"
                        OR          transactions	LIKE "Card purchase"
                        OR          transactions	LIKE "e-payment"
                        OR          transactions	LIKE "Service fee"
                        OR          transactions	LIKE "Corporate Payments"                      
		'                  
                        
		);
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError("Result error: " . $sql);
        }
        $row = mysqli_fetch_object($result);
		mysqli_free_result($result);
                return $row;
        }
        // get sum of incoming payments
        public function getIncominggBanking(){
		$sql = sprintf('
			SELECT      SUM(euro) AS incoming
                        FROM        allbank 
                        WHERE       transactions     = "Reference Payment"
                        OR          transactions     = "Deposit"                
		'                  
                        
		);
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError("Result error: " . $sql);
        }
        $row = mysqli_fetch_object($result);
		mysqli_free_result($result);
                return $row;
        }
        // get call bank info
        public function getAllBankInfo(){
		$sql = sprintf('
			SELECT         *                                    		
			FROM		bank2019                      
                        ORDER BY        recid ASC
		'
                        
                );
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError("Result error: " . $sql);
        }
        $queryresult = array();
		while ($row = mysqli_fetch_object($result)) {
			$queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;
        }
         // get all daily goals 
        public function getDailyGoals(){
		$sql = sprintf('
			SELECT          *                                    		
			FROM		dailygoals
                        ORDER BY        recid ASC
		' 
                );
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError("Result error: " . $sql);
        }
        $queryresult = array();
		while ($row = mysqli_fetch_object($result)) {
			$queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;
        }
        // get daily goals by id
        public function getDailyGoalsByID($userid){
		$sql = sprintf('
			SELECT          *                                    		
			FROM		dailygoals 
                        WHERE           userid=%d
                        ORDER BY        recid DESC
		',
                        
                     $this->esc($userid)   
                );
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError("Result error: " . $sql);
        }
        $queryresult = array();
		while ($row = mysqli_fetch_object($result)) {
			$queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;
        }
         // Add accounting info
        public function addDailyGoalByID($userid, $project, $appointments, $notes, $startdate, $enddate){
		$sql = sprintf("
                        INSERT INTO      dailygoals
                                        (userid, project, appointments, notes, starttime, endtime)
			VALUES		('%d', '%s', '%s', '%s', '%s', '%s')	
		",
                       $this->esc($userid), $this->esc($project), $this->esc($appointments), $this->esc($notes), $this->esc($startdate), $this->esc($enddate)
		);
		mysqli_query($this->_Link, $sql) or $this->callError($sql);
		return mysqli_insert_id($this->_Link);
        }
        public function deleteDailyGoal($userid, $recid){
		$sql = sprintf("
			UPDATE          dailygoals
                        SET             delet=1
                        WHERE           (userid = %d)
                            AND         (recid = %d)
		",
                       $this->esc($userid), $this->esc($recid)                
		);
		mysqli_query($this->_Link, $sql) or $this->callError($sql);
		return mysqli_insert_id($this->_Link);
        }
        public function addCorrectDate($key, $tempdate){
		$sql = sprintf("
			UPDATE          allbank
                        SET             t_date='%s'
                        WHERE           (recid = %d)
		",
                       $this->esc($tempdate), $this->esc($key)                
		);
		mysqli_query($this->_Link, $sql) or $this->callError($sql);
		return mysqli_insert_id($this->_Link);
        }
        // get accounting
        public function getAccounting(){
		$sql = sprintf('
			SELECT		*                                 
			FROM		accounting  
                        WHERE           deleted = 0
                        ORDER BY        id ASC
		');
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError("Result error: " . $sql);
        }
        $queryresult = array();
		while ($row = mysqli_fetch_object($result)) {
			$queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;
        }
        public function deleteAccounting($recid){
		$sql = sprintf("
			UPDATE          accounting
                        SET             deleted = 1
                        WHERE           (id = %d)
		",
                       $this->esc($recid)                
		);
		mysqli_query($this->_Link, $sql) or $this->callError($sql);
		return mysqli_insert_id($this->_Link);
        }
        // Add profile image src
        public function addAccounting($month, $year, $revenue, $profit, $salaries, $wages, $bills){
		$sql = sprintf("
			INSERT INTO      accounting
			               (month, year, revenue, profit, salaries, wages, bills )
			VALUES		('%s', '%s', '%s', '%s', '%s', '%s', '%s')
		",
                       $this->esc($month), $this->esc($year), $this->esc($revenue), $this->esc($profit), $this->esc($salaries), $this->esc($wages), $this->esc($bills)
		);
		mysqli_query($this->_Link, $sql) or $this->callError($sql);
		return mysqli_insert_id($this->_Link);
        }
        // get profile src by id
        public function getImageSrcByID($id){
		$sql = sprintf('
			SELECT          *                                    		
			FROM		image 
                        WHERE           id=%d
                        ORDER BY        id DESC
		',
                        
                     $this->esc($id)   
                );
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError("Result error: " . $sql);
        }
        $queryresult = array();
		while ($row = mysqli_fetch_object($result)) {
			$queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;
        }
        // Add accounting info
        public function addImageSrc($actual_image_name){
		$sql = sprintf("
                        INSERT INTO      image
                                        (src)
			VALUES		('%s')	
		",
                       $this->esc($actual_image_name)
		);
		mysqli_query($this->_Link, $sql) or $this->callError($sql);
		return mysqli_insert_id($this->_Link);
        }
	// MySql Escape Shortcut
	function esc($string){ return mysqli_real_escape_string($this->_Link, $string); }        

	public function getSHA($str){
		$rstr = "";
		$rstr = base64_encode(hash("sha256", trim($str)));
		return $rstr;
	}
    
	// Call Error
	function callError($details='') {
		if($this->debug){
			print('
				<div style="width: 500px; background-color: #990000; color: #FFFFFF; padding: 20px;">
				<H4>Database Error</H4>
				<P>Details: ' . $details . '</P>
				<P>MySQL Error: ' . mysqli_error($this->_Link) . '</P>
				</div>
			');
		}
	}        

        public function create_slug($string){
            $text = preg_replace('~[^\pL\d]+~u', '-', $string);
            $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
            $text = preg_replace('~[^-\w]+~', '', $text);
            $text = trim($text, '-');
            $text = preg_replace('~-+~', '-', $text);
            $text = strtolower($text);

            if (empty($text)) {
              return 'n-a';
            }

            return $text;            
        }
        
        public function charsetConvert($istring){
            $rstring = "";
            try {
                $rstring = iconv('CP1255', 'UTF-8//IGNORE', $istring);
            } catch(Exception $e) { $rstring = $istring; print($istring); }
            return $rstring;
        }
        
        public function runQuery($string)
        {
		mysqli_query($this->_Link, $string) or $this->callError($string);
		return mysqli_insert_id($this->_Link);
        }      
        
        public function runQueryArray($string)
        {
		$result = mysqli_query($this->_Link, $string);
		if (!$result) {
            $this->callError("Result error: " . $string);
        }
        $queryresult = array();
		while ($row = mysqli_fetch_object($result)) {
			$queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;            
        }

        public function runQueryOne($string)
        {
		$result = mysqli_query($this->_Link, $string);
		if (!$result) {
            $this->callError("Result error: " . $string);
        }
        $row = mysqli_fetch_object($result);
		mysqli_free_result($result);
		return $row;
        } 
         // get schedules 
        public function getSchedules(){
		$sql = sprintf('
                SELECT dow.id
                    ,dow.name
                    ,sd.from_time
                    ,sd.thru_time
                    ,sd.description

                    FROM days_of_week      dow

                    JOIN users             u
                      ON u.userid              = 1

                    LEFT OUTER JOIN weekly_schedules  ws
                      ON ws.user_id        = u.userid


                    JOIN schedules         s
                      ON s.user_id         = u.userid
                     AND ( (ws.week_no IS NULL AND s.is_default = "Y")
                        OR (ws.week_no IS NOT NULL AND s.id = ws.schedule_id)
                         )

                    LEFT OUTER JOIN (SELECT DISTINCT schedule_id, day_of_week_id
                            FROM schedules        ss
                            JOIN schedule_details sds
                              ON ss.user_id       = 1
                             AND sds.schedule_id  = ss.id
                             AND sds.day_of_week_id IS NOT NULL
                         ) sdow
                      ON sdow.schedule_id    = s.id
                     AND sdow.day_of_week_id = dow.id

                    JOIN schedule_details  sd
                      ON sd.schedule_id      = s.id
                     AND ( (sdow.day_of_week_id IS NOT NULL AND sd.day_of_week_id = sdow.day_of_week_id)
                        OR (sdow.day_of_week_id IS NULL AND sd.day_of_week_id IS NULL)
                         )

                   ORDER BY dow.id, sd.from_time
		' 
                );
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError("Result error: " . $sql);
        }
        $queryresult = array();
		while ($row = mysqli_fetch_object($result)) {
			$queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;
        }
       
         // Add profile image src
        public function addEvent($title, $start, $end, $color){
		$sql = sprintf("
			INSERT INTO      events
			               (title, start, end, color)
			VALUES		('%s', '%s', '%s', '%s')
		",
                       $this->esc($title), $this->esc($start), $this->esc($end), $this->esc($color)
		);
		mysqli_query($this->_Link, $sql) or $this->callError($sql);
		return mysqli_insert_id($this->_Link);
        }
        public function updateEvent($start, $end, $id){
		$sql = sprintf("
			UPDATE          events
                        SET  start = %s, end = %s
                        WHERE id = %d
		",
                       $this->esc($start), $this->esc($end), $this->esc($id)                
		);
		mysqli_query($this->_Link, $sql) or $this->callError($sql);
		return mysqli_insert_id($this->_Link);
        }
         public function updateEventTitle($title, $color, $id){
		$sql = sprintf("
			UPDATE          events
                        SET  title = %s, color = %s
                        WHERE id = %d
		",
                       $this->esc($title), $this->esc($color), $this->esc($id)                
		);
		mysqli_query($this->_Link, $sql) or $this->callError($sql);
		return mysqli_insert_id($this->_Link);
        }
        public function deleteEvent($id){
		$sql = sprintf("
			UPDATE          events
                        SET             deleted = 1
                        WHERE           (id = %d)
		",
                       $this->esc($id)                
		);
		mysqli_query($this->_Link, $sql) or $this->callError($sql);
		return mysqli_insert_id($this->_Link);
        }
        // get accounting
        public function getEvents(){
		$sql = sprintf('
			SELECT		*                                 
			FROM		events                    
		');
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError("Result error: " . $sql);
        }
        $queryresult = array();
		while ($row = mysqli_fetch_object($result)) {
			$queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;
        }
        
        // Generic add function for xsls
        public function addLeadsFromXlsx($value1, $value2, $value3,  $value4, $value5, $value6, $value7, $value8, $value9, $value10, $value11, $value12, $value13, $value14, $value15, $value16, $value17, $value18, $value19){
		$sql = sprintf("
			INSERT INTO      irrtv_leads
                                        (puhelinnumero, ryhma, etunimi, sukunimi, osoite, postinumero, kaupunki, toinen_nimi, sahkoposti, asiakasnumero, sukupuoli, syntymaaika, Sahkopostijakelu, postituskielto, kuollut, osoite_tuntematon, kieli, kuvaus, createdate)
			VALUES		('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s', '%s','%s','%s','%s','%s','%s','%s',NOW())
		",
                       $this->esc($value1), $this->esc($value2), $this->esc($value3), $this->esc($value4),$this->esc($value5), $this->esc($value6), $this->esc($value7), $this->esc($value8), $this->esc($value9), $this->esc($value10), $this->esc($value11), $this->esc($value12), $this->esc($value13), $this->esc($value14), $this->esc($value15), $this->esc($value16), $this->esc($value17), $this->esc($value18), $this->esc($value19)
		);
		mysqli_query($this->_Link, $sql) or $this->callError($sql);
		return mysqli_insert_id($this->_Link);
        }
        public function getProjectLeadList($table){
		$sql = sprintf('
			SELECT		v.*, u.userid, u.firstname, u.lastname
			FROM		%s v 
                        LEFT OUTER JOIN users u 
                                    ON  v.userid = u.userid
                        WHERE           v.deleted != 1
                        ORDER BY        v.recid ASC
		',
                       $this->esc($table) 
                );
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError("Result error: " . $sql);
        }
        $queryresult = array();
		while ($row = mysqli_fetch_object($result)) {
			$queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;
        }
        public function getHomePage($lang){
		$sql = sprintf('
			SELECT              *
                        FROM                homepage
                        WHERE               lang="%s"
		',
                   $this->esc($lang)  
                );
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError("Result error: " . $sql);
        }
        $queryresult = array();
		while ($row = mysqli_fetch_object($result)) {
			$queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;
        } 
        public function getTeamSection(){
		$sql = sprintf('
			SELECT              *
                        FROM                team                       
                        ORDER BY            order_num
		'  
                );
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError("Result error: " . $sql);
        }
        $queryresult = array();
		while ($row = mysqli_fetch_object($result)) {
			$queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;
        } 
        public function getMenuItems(){
		$sql = sprintf('
			SELECT              *
                        FROM                web_menu  
                        WHERE               active=1
                        ORDER BY            order_num
		'  
                );
		$result = mysqli_query($this->_Link, $sql);
                if (!$result) {
                    $this->callError("Result error: " . $sql);
                }
                $queryresult = array();
                        while ($row = mysqli_fetch_object($result)) {
			$queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;
        }
         public function getMenuItemWithID($table, $recid){
		$stmt = $this->_Link->prepare("SELECT * FROM " . $table . " WHERE recid = ?");
                $stmt->bind_param("i", $recid);
                $stmt->execute();
                $result = $stmt->get_result();
                $myrow = $result->fetch_assoc();             
                $stmt->close();               
                return $myrow;
        }
        public function getSubMenuItems(){
		$sql = sprintf('
			SELECT              *
                        FROM                web_sub_menu                       
                        ORDER BY            order_num
		'  
                );
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError("Result error: " . $sql);
        }
        $queryresult = array();
		while ($row = mysqli_fetch_object($result)) {
			$queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;
        }
        public function getWebInfo($table, $link, $sortorder, $lang){
            
            if($lang == "en"){               
                $sql = sprintf('                 
			SELECT              *
                        FROM                %s
                        WHERE               heading_en = "%s"
                        ORDER BY            %s
		',
                       $this->esc($table), $this->esc($link), $this->esc($sortorder)  
                );
            }else{                
                $sql = sprintf('                 
			SELECT              *
                        FROM                %s
                        WHERE               heading_fi = "%s"
                        ORDER BY            %s
		',
                       $this->esc($table), $this->esc($link), $this->esc($sortorder)  
                );
            }
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError("Result error: " . $sql);
        }
        $queryresult = array();
		while ($row = mysqli_fetch_object($result)) {
			$queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;
        } 
       public function getBlogData(){
		$sql = sprintf('
			SELECT              *
                        FROM                blog                       
		' 
                );
		$result = mysqli_query($this->_Link, $sql);
		if (!$result) {
            $this->callError("Result error: " . $sql);
        }
        $queryresult = array();
		while ($row = mysqli_fetch_object($result)) {
			$queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;
        } 
        public function getBlogDataWithId($recid){
		$sql = sprintf('
			SELECT              b.recid, b.Title, b.sub_title, b.content, b.content_p2, b.createdate, b.quote, b.main_image, b.Post_image2, b.Post_image3, b.category, b.author_id,
                                            a.firstname, a.author_title, a.author_description, a.author_sig, a.author_image
                        FROM                blog b
                        INNER JOIN          users a ON a.userid = b.author_id
                        WHERE               title="%s"
		',
                   $this->esc($recid)     
                );
		$result = mysqli_query($this->_Link, $sql);
                if (!$result) {
                    $this->callError("Result error: " . $sql);
                }
                $queryresult = array();
                        while ($row = mysqli_fetch_object($result)) {
			$queryresult[] = $row;
		}
		mysqli_free_result($result);
		return $queryresult;
        }
        public function getBlogDataWithId1($recid){
		$stmt = $this->_Link->prepare("SELECT b.recid, b.Title, b.sub_title, b.content, b.content_p2, b.createdate, b.quote, b.main_image, b.Post_image2, b.Post_image3, b.category, b.author_id,
                                            a.firstname, a.author_title, a.author_description, a.author_sig, a.author_image FROM blog b INNER JOIN users a ON a.userid = b.author_id WHERE title = ?");
                $stmt->bind_param("s", $recid);
                $stmt->execute();
                $result = $stmt->get_result();
                $myrow = $result->fetch_assoc();             
                $stmt->close();               
                return $myrow;
        }
        public function setBlogInfo($id, $title, $descript, $sig_image, $blog_image){
		$sql = sprintf("
			UPDATE          users
                        SET             blog_title = '%s', blog_description = '%s', sig_image =  '%s' , blog_image =  '%s'
                        WHERE           userid = %d
		",
                       $this->esc($title), $this->esc($descript), $this->esc($sig_image),$this->esc($blog_image), $this->esc($id)               
		);
		mysqli_query($this->_Link, $sql) or $this->callError($sql);
		return mysqli_insert_id($this->_Link);
        }
        public function addBlogPost($main_image, $blogtitle, $subtitle="", $content="", $author_id) {   
            
            $stmt = $this->_Link->prepare("INSERT INTO blog (main_image, Title, sub_title, content, author_id, createdate) VALUES (?, ?, ?, ?,?, NOW())");
            $stmt->bind_param("ssssi",  $main_image, $blogtitle, $subtitle, $content, $author_id);
            $stmt->execute();
            $stmt->close();          
        }
        public function updateBlogPost($main_image, $blogtitle, $subtitle, $content, $recid) {
            
            $stmt = $this->_Link->prepare("UPDATE blog SET main_image = ?, Title = ?, sub_title = ?, content = ? WHERE recid = ?");
            $stmt->bind_param("ssssi",$main_image, $blogtitle, $subtitle, $content, $recid);
            $stmt->execute();
            $stmt->close();          
        }
        
        public function getBlog(){
            $stmt = $this->_Link-> prepare('SELECT Title, content FROM blog'); 
            $stmt -> execute(); 
            $stmt -> store_result();          
            $stmt -> bind_result($Title, $content); 
            
        }
        public function getlist($table){
            $stmt = $this->_Link->prepare("SELECT * FROM " . $table . " WHERE deleted !=1");  
            $stmt->execute();
            $result = $stmt->get_result(); // get the mysqli result            
            $da = $result->fetch_all(MYSQLI_ASSOC); // fetch data  
            return $da;            
        }
        public function getlistByMonth($table, $month){
            $stmt = $this->_Link->prepare("SELECT * FROM " . $table . " WHERE deleted !=1 AND MONTH(EntryDate)= ? ");  
            if ( false===$stmt ) {           
                die('prepare() failed: ' . htmlspecialchars($this->_Link->error));
            }
            $rc =  $stmt->bind_param("s", $month);
            if ( false===$rc ) {               
                die('bind_param() failed: ' . htmlspecialchars($stmt->error));
            }
            $rc = $stmt->execute();
            if ( false===$rc ) {
                die('execute() failed: ' . htmlspecialchars($stmt->error));
            }
            $result = $stmt->get_result(); // get the mysqli result            
            $da = $result->fetch_all(MYSQLI_ASSOC); // fetch data  
            return $da;            
        }
        public function getDataWithId($table, $id){
            $stmt = $this->_Link->prepare("SELECT * FROM " . $table . " WHERE recid = ?");
            $stmt->bind_param("s", $id);
            $stmt->execute();
            $result = $stmt->get_result(); // get the mysqli result            
            $da = $result->fetch_all(MYSQLI_ASSOC); // fetch data  
            return $da;            
        }
        public function updateWebMenu($table, $heading_en, $heading_fi, $db_category, $hasSubs, $link_en, $link_fi, $order_num, $top_id, $recid){
            $stmt = $this->_Link->prepare("UPDATE web_menu SET heading_en = ?, heading_fi = ?, db_category = ?, hasSubs = ?, link_en = ?, link_fi = ?, order_num = ?, top_id = ? WHERE recid = ?");
            $stmt->bind_param("sssissiii", $heading_en, $heading_fi, $db_category, $hasSubs, $link_en, $link_fi, $order_num, $top_id, $recid);
            $stmt->execute();
            $stmt->close();     
        }
        public function updateFooter( $heading_en, $heading_fi, $content_en, $content_fi, $recid, $styles, $classes){
            $stmt = $this->_Link->prepare("UPDATE footer SET  heading_en = ?, heading_fi = ?, content_en = ?, content_fi = ?, styles = ?, classes = ? WHERE recid = ?");
            if ( false===$stmt ) {           
                die('prepare() failed: ' . htmlspecialchars($this->_Link->error));
            }
            $rc = $stmt->bind_param("ssssssi",  $heading_en, $heading_fi, $content_en, $content_fi, $styles, $classes, $recid);
            if ( false===$rc ) {               
                die('bind_param() failed: ' . htmlspecialchars($stmt->error));
            }
            $rc = $stmt->execute();
            if ( false===$rc ) {
                die('execute() failed: ' . htmlspecialchars($stmt->error));
            }
            $stmt->close();     
        }
        public function delFooter($recid){
            $stmt = $this->_Link->prepare("UPDATE footer SET deleted = ? WHERE recid = ?");
            $del = 1;
            if ( false===$stmt ) {           
                die('prepare() failed: ' . htmlspecialchars($this->_Link->error));
            }
            $rc = $stmt->bind_param("ii", $del, $recid);
            if ( false===$rc ) {               
                die('bind_param() failed: ' . htmlspecialchars($stmt->error));
            }
            $rc = $stmt->execute();
            if ( false===$rc ) {
                die('execute() failed: ' . htmlspecialchars($stmt->error));
            }
            $stmt->close();     
        }
        public function addFooter($heading_en, $heading_fi, $content_en, $content_fi, $styles, $classes){         
            $stmt = $this->_Link->prepare("INSERT INTO footer (heading_en, heading_fi, content_en, content_fi, styles, classes) VALUES (?, ?, ?, ?, ?, ?)");
             if ( false===$stmt ) {           
                die('prepare() failed: ' . htmlspecialchars($this->_Link->error));
            }
            $rc = $stmt->bind_param("ssssss", $heading_en, $heading_fi, $content_en, $content_fi, $styles, $classes);
            if ( false===$rc ) {               
                die('bind_param() failed: ' . htmlspecialchars($stmt->error));
            }
            $rc = $stmt->execute();
            if ( false===$rc ) {
                die('execute() failed: ' . htmlspecialchars($stmt->error));
            }
            $stmt->close();               
        }
        //add function for xsls for bills
        public function addGCBills($value1, $value2, $value3,  $value4, $value5, $value6, $value7, $value8, $value9, $value10, $value11, $value12, $value13){         
            $stmt = $this->_Link->prepare("INSERT INTO GC_Bills (Invoice_no, Name, Company_ID, Invoice_date, Due_date, Payment_date, Excluding_VAT, Total_VAT, Total_sum, Bank_reference_code, Account_number, Invoice_channel, Original_invoice_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssssssss",  $value1, $value2, $value3,  $value4, $value5, $value6, $value7, $value8, $value9, $value10, $value11, $value12, $value13);
            $stmt->execute();       
            $stmt->close();          
        }
        public function updateSuccessMessage($message, $page, $sub_heading, $heading) {                   
            $stmt = $this->_Link->prepare("UPDATE pages SET " . $sub_heading . " = ? WHERE " . $heading . " = ?");
            $stmt->bind_param("ss",$message, $page);
            $stmt->execute();
            $stmt->close();          
        }
        //add info to online payments table
        public function addPayment($value1, $value2, $value3,  $value4, $value5){         
            $stmt = $this->_Link->prepare("INSERT INTO online_payments (name, description, stripe_id, amount, ip_address) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss",  $value1, $value2, $value3,  $value4, $value5);
            $stmt->execute();       
            $stmt->close();          
        }
        public function updateHomepage($recid, $db_main_image, $intro_text, $sub_quote_text, $quote_text, $db_wwa_image, $wwa_title, $wwa_text, $tagline, $contact_text, $db_s1_image, $s1_heading, $s1_text, $db_s2_image, $s2_heading, $s2_text, $db_s3_image, $s3_heading, $s3_text){
            $stmt = $this->_Link->prepare("UPDATE homepage SET main_image = ?, intro_text = ?, sub_quote_text = ?, quote_text = ?, wwa_image = ?, wwa_title = ?,wwa_text = ?, tag_line = ?, contact_text = ?, s1_image = ?, s1_heading = ?, s1_text = ?, s2_image = ?, s2_heading = ?, s2_text = ?, s3_image = ?, s3_heading = ?, s3_text = ? WHERE recid = ?");
            if ( false===$stmt ) {           
                die('prepare() failed: ' . htmlspecialchars($this->_Link->error));
            }
            //echo $db_main_image . " " .  $intro_text . "<br>" .  $sub_quote_text . "<br> " .  $quote_text . " <br>" .  $db_wwa_image . "<br> " .  $wwa_title . " <br>" .  $wwa_text . "<br> " .  $tagline . "<br> " .  $contact_text . " <br>" .  $db_s1_image . " <br>" .  $s1_heading . " <br>" .  $s1_text . "<br> " .  $db_s2_image . "<br> " .  $s2_heading . " <br>" .  $s2_text . "<br> " .  $db_s3_image . "<br> " .  $s3_heading . "<br> " . $s3_text;
            $rc = $stmt->bind_param("ssssssssssssssssssi", $db_main_image, $intro_text, $sub_quote_text, $quote_text, $db_wwa_image, $wwa_title, $wwa_text, $tagline, $contact_text, $db_s1_image, $s1_heading, $s1_text, $db_s2_image, $s2_heading, $s2_text, $db_s3_image, $s3_heading, $s3_text, $recid);
            if ( false===$rc ) {               
                die('bind_param() failed: ' . htmlspecialchars($stmt->error));
            }
            $rc = $stmt->execute();
            if ( false===$rc ) {
                die('execute() failed: ' . htmlspecialchars($stmt->error));
            }
            $stmt->close();     
        }
        
        // Generic add function for xsls
        public function addReportsFromXlsx($value1, $value2, $value3,  $value4, $value5, $value6, $value7, $value8, $value9, $value10, $value11, $value12, $value13, $value14, $value15, $value16){
            $rc = $stmt = $this->_Link->prepare("INSERT INTO soittolinja_reporting (Projekti, Hitrate, Läpiviety, Kaupat, Tapaamiset, Korvaavia, Ei, Hylätty, Soitot, Työaika, Puheaika, Soitot_tunti, Soitot_kauppa, Työaika_kauppa, Tulos, EntryDate) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if ( false===$stmt ) {           
                die('prepare() failed: ' . htmlspecialchars($this->_Link->error));
            }
            $rc =  $stmt->bind_param("ssssssssssssssss",  $value1, $value2, $value3,  $value4, $value5, $value6, $value7, $value8, $value9, $value10, $value11, $value12, $value13,  $value114, $value15, $value16);
            if ( false===$rc ) {               
                die('bind_param() failed: ' . htmlspecialchars($stmt->error));
            }
            $rc = $stmt->execute();
            if ( false===$rc ) {
                die('execute() failed: ' . htmlspecialchars($stmt->error));
            }
            $stmt->close(); 
        }
        // Generic add function for xsls
        public function addProjectReportsFromXlsx($value1, $value2, $value3,  $value4, $value5, $value6, $value7, $value8, $value9, $value10, $value11, $value12, $value13, $value14, $value15, $value16, $value17, $value18, $value19){
            $rc = $stmt = $this->_Link->prepare("INSERT INTO soittolinja_projects (Company, Business_id, First_name, Last_name, Phone_number, Agentin_nimi, Tila, Comment, Soiton_lopputulos, Soiton_lopputulos_lisätieto, Soittolista, Kampanja, Call_time, Kellonaika, Title, Category, Duration, Talktime, Profit) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?)");
            if ( false===$stmt ) {           
                die('prepare() failed: ' . htmlspecialchars($this->_Link->error));
            }
            $rc =  $stmt->bind_param("ssssssssssssssssddd",  $value1, $value2, $value3,  $value4, $value5, $value6, $value7, $value8, $value9, $value10, $value11, $value12, $value13, $value14, $value15, $value16, $value17, $value18, $value19);
            if ( false===$rc ) {               
                die('bind_param() failed: ' . htmlspecialchars($stmt->error));
            }
            $rc = $stmt->execute();
            if ( false===$rc ) {
                die('execute() failed: ' . htmlspecialchars($stmt->error));
            }
            $stmt->close(); 
        }
        
        public function getReportCount($month){
            $rc = $stmt = $this->_Link->prepare("SELECT R.recid, R.Projekti, sum(R.Kaupat) as Count, P.company, P.price, P.main_project, P.goal FROM gc_company_prices P INNER JOIN soittolinja_reporting R ON R.Projekti = P.company WHERE R.deleted !=1 AND R.Korvaavia=0 AND MONTH(R.EntryDate)= ? GROUP BY P.main_project"); 
            if ( false===$stmt ) {           
                die('prepare() failed: ' . htmlspecialchars($this->_Link->error));
            }
            $rc =  $stmt->bind_param("s", $month);
            if ( false===$rc ) {               
                die('bind_param() failed: ' . htmlspecialchars($stmt->error));
            }
            $rc = $stmt->execute();
            if ( false===$rc ) {
                die('execute() failed: ' . htmlspecialchars($stmt->error));
            }
            $arr = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            //if(!$arr) exit('No rows');           
            $stmt->close();
            return $arr;
        }
        
        public function getProjectReportCount($month){
            $rc = $stmt = $this->_Link->prepare("SELECT R.recid, R.Kampanja, sum(R.Soiton_lopputulos) as Count, P.company, P.price, P.main_project, P.goal FROM gc_company_prices P INNER JOIN soittolinja_projects R ON R.Kampanja = P.company WHERE R.deleted !=1 AND R.Soiton_lopputulos_lisätieto = '' AND MONTH(R.Call_time)= ? GROUP BY P.main_project"); 
            if ( false===$stmt ) {           
                die('prepare() failed: ' . htmlspecialchars($this->_Link->error));
            }
            $rc =  $stmt->bind_param("s", $month);
            if ( false===$rc ) {               
                die('bind_param() failed: ' . htmlspecialchars($stmt->error));
            }
            $rc = $stmt->execute();
            if ( false===$rc ) {
                die('execute() failed: ' . htmlspecialchars($stmt->error));
            }
            $arr = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            //if(!$arr) exit('No rows');           
            $stmt->close();
            return $arr;
        }
        public function getAgenttReportCount($month){
            $rc = $stmt = $this->_Link->prepare(" SELECT R.recid, R.Agentin_nimi, sum(R.Soiton_lopputulos) as Count FROM soittolinja_projects R  WHERE R.deleted !=1 AND R.Soiton_lopputulos_lisätieto = '' AND MONTH(R.Call_time)= ? GROUP BY R.Agentin_nimi"); 
            if ( false===$stmt ) {           
                die('prepare() failed: ' . htmlspecialchars($this->_Link->error));
            }
            $rc =  $stmt->bind_param("s", $month);
            if ( false===$rc ) {               
                die('bind_param() failed: ' . htmlspecialchars($stmt->error));
            }
            $rc = $stmt->execute();
            if ( false===$rc ) {
                die('execute() failed: ' . htmlspecialchars($stmt->error));
            }
            $arr = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            //if(!$arr) exit('No rows');           
            $stmt->close();
            return $arr;
        }
        public function ClearProjectReport(){
            $rc = $stmt = $this->_Link->prepare("TRUNCATE soittolinja_projects"); 
            if ( false===$stmt ) {           
                die('prepare() failed: ' . htmlspecialchars($this->_Link->error));
            }
            
            $rc = $stmt->execute();
            if ( false===$rc ) {
                die('execute() failed: ' . htmlspecialchars($stmt->error));
            }
            
            $stmt->close();
           
        }
        
        public function delItem($recid, $table){
            $stmt = $this->_Link->prepare("UPDATE " . $table . " SET deleted = ? WHERE recid = ?");
            $del = 1;
            if ( false===$stmt ) {           
                die('prepare() failed: ' . htmlspecialchars($this->_Link->error));
            }
            $rc = $stmt->bind_param("ii", $del, $recid);
            if ( false===$rc ) {               
                die('bind_param() failed: ' . htmlspecialchars($stmt->error));
            }
            $rc = $stmt->execute();
            if ( false===$rc ) {
                die('execute() failed: ' . htmlspecialchars($stmt->error));
            }
            $stmt->close();     
        }
        
        public function editBudgetItem($value1, $value2, $value3,  $value4, $value5, $value6){
            $rc = $stmt = $this->_Link->prepare("update budget SET company = ?, amount = ?, duedate = ?, paiddate= ?, personal =  ?  WHERE recid = ?");
            if ( false===$stmt ) {           
                die('prepare() failed: ' . htmlspecialchars($this->_Link->error));
            }
            $rc =  $stmt->bind_param("ssssdd",  $value1, $value2, $value3,  $value4, $value5, $value6);
            if ( false===$rc ) {               
                die('bind_param() failed: ' . htmlspecialchars($stmt->error));
            }
            $rc = $stmt->execute();
            if ( false===$rc ) {
                die('execute() failed: ' . htmlspecialchars($stmt->error));
            }
            $stmt->close(); 
        }
        public function addBudgetItem($value1, $value2, $value3,  $value4, $value5){
            $rc = $stmt = $this->_Link->prepare("INSERT INTO budget (company, amount, duedate, paiddate, personal) VALUES (?, ?, ?, ?, ?)");
            if ( false===$stmt ) {           
                die('prepare() failed: ' . htmlspecialchars($this->_Link->error));
            }
            $rc =  $stmt->bind_param("ssssd",  $value1, $value2, $value3,  $value4, $value5);
            if ( false===$rc ) {               
                die('bind_param() failed: ' . htmlspecialchars($stmt->error));
            }
            $rc = $stmt->execute();
            if ( false===$rc ) {
                die('execute() failed: ' . htmlspecialchars($stmt->error));
            }
            $stmt->close(); 
        }
 }
 
 
 
 