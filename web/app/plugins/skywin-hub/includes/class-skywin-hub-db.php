<?php
defined('ABSPATH') || exit;
if (!class_exists('Skywin_Hub_DB')) :
	class Skywin_Hub_DB
	{
		protected static $_instance = null;
		private $db;
		private $errors;

		public static function instance()
		{
			if ( is_null(self::$_instance) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}
		public function __construct(){
			$this->db = $this->connection();
		}
		private function connection()
		{
			$host 		= get_option("skywin_hub_db_host");
			$name 		= get_option("skywin_hub_db_name");
			$username 	= get_option("skywin_hub_db_username");
			$port 		= get_option("skywin_hub_db_port");
			$password 	= encrypt_decrypt(get_option("skywin_hub_db_password"), 'd');
			if ( !$host || !$name || !$username || !$port || !$password ) {
				error_log('skywin_db_credentials_error: Missing credentials!');
				return new WP_Error('skywin_db_credentials_error', 'Missing credentials!');
			}
			try {
				$this->db = new PDO(
					"mysql:host=" . $host . ";dbname=" . $name . ";port=" . $port,
					$username,
					$password,
					array(
						PDO::ATTR_TIMEOUT => 10,
						PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
						PDO::ATTR_PERSISTENT => true,
					)
				);
				$this->db->exec("set names utf8");
			} catch (PDOException $exception) {
				error_log('skywin_pdo_error: ' . json_encode($exception));
				return new WP_Error('skywin_pdo_error', json_encode($exception->getMessage()));
			}
			return $this->db;
		}
		public function status()
		{
			$result = false;
			if( is_wp_error($this->db) ) return $result;
			$sql = "SELECT 1";
			try {
				$stmt = $this->db->prepare($sql);
				$stmt->execute();
				$result = $stmt->fetch(PDO::FETCH_ASSOC);
			} catch (PDOException $exception) {
				error_log('skywin_sql_error: You are not doing it right.');
			}
			return $result;
		}
		private function memberphone($id = NULL, $phoneType = NULL)
		{
			$results = [];
			if( is_wp_error($this->db) || !$id) return $results;
			$sql = "SELECT PhoneType, PhoneNo FROM memberphone WHERE InternalNo LIKE :id AND PhoneType LIKE :ptype";
			try {
				$id = trim($id);
				$stmt = $this->db->prepare($sql);
				$stmt->bindParam(':id', $id, PDO::PARAM_STR);
				$stmt->bindParam(':ptype', $phoneType, PDO::PARAM_STR);
				$stmt->execute();
				$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
				foreach ($results as $key => $phone) {
					if ( $phoneType == $phone['PhoneType']) {
						$results['PhoneNo'] = $phone['PhoneNo'];
					} else {
						$results[$key] = [$phone['PhoneType'] => $phone['PhoneNo']];
					}
				}
			} catch (PDOException $exception) {
				error_log('skywin_sql_error: ' . json_encode($exception->getMessage()));
			}
			return $results;
		}
		public function clubs($InUse = 'Y')
		{
			if( is_wp_error($this->db) ) return [];
			$sql = "SELECT Club, Name, CountryCode, Emailaddress, InUse FROM club WHERE InUse LIKE ? ORDER BY Name";
			$results = [];
			try {
				$InUse = esc_sql($InUse);
				$stmt = $this->db->prepare($sql);
				$stmt->execute(array($InUse));
				$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
			} catch (PDOException $exception) {
				error_log('SQLError: ' . json_encode($exception->getMessage()));
			}
			return $results;
		}
		public function pid_exists($search = NULL)
		{
			if( is_wp_error($this->db) || !$search)
				return new WP_Error('skywin_sql_error', 'You are not doing it right!');

			$sql = "SELECT PID FROM member WHERE PID LIKE :search";
			try {
				$search = trim($search);
				$stmt = $this->db->prepare($sql);
				$stmt->bindParam(':search', $search, PDO::PARAM_STR);
				$stmt->execute();
				$result = $stmt->fetch(PDO::FETCH_ASSOC);
			} catch (PDOException $exception) {
				error_log('skywin_sql_error: ' . json_encode($exception->getMessage()));
				return new WP_Error('skywin_sql_error', json_encode($exception->getMessage()));
			}
			return $result;
		}
		public function email_exists($search = NULL)
		{
			if( is_wp_error($this->db) || !$search )
				return new WP_Error('skywin_sql_error', 'You are not doing it right!');;

			$sql = "SELECT Emailaddress FROM member WHERE Emailaddress LIKE :search";
			try {
				$stmt = $this->db->prepare($sql);
				$searchValue = trim($search);
				$stmt->bindParam(':search', $searchValue, PDO::PARAM_STR);
				$stmt->execute();
				$result = $stmt->fetch(PDO::FETCH_ASSOC);
			} catch (PDOException $exception) {
				error_log('skywin_sql_error: ' . json_encode($exception->getMessage()));
				$result = new WP_Error('skywin_sql_error', json_encode($exception->getMessage()));
			}
			return $result;
		}
		public function memberno_exists($search = NULL)
		{
			if( is_wp_error($this->db) || !$search )
				return new WP_Error('skywin_sql_error', 'You are not doing it right!');;

			$sql = "SELECT MemberNo FROM member WHERE MemberNo LIKE :search";
			try {
				$stmt = $this->db->prepare($sql);
				$searchValue = trim($search);
				$stmt->bindParam(':search', $searchValue, PDO::PARAM_STR);
				$stmt->execute();
				$result = $stmt->fetch(PDO::FETCH_ASSOC);
			} catch (PDOException $exception) {
				error_log('skywin_sql_error: ' . json_encode($exception->getMessage()));
				$result = new WP_Error('skywin_sql_error', json_encode($exception->getMessage()));
			}
			return $result;
		}
		public function internalNo_exists($search = NULL)
		{
			if( is_wp_error($this->db) || !$search )
				return new WP_Error('skywin_sql_error', 'You are not doing it right!');;

			$sql = "SELECT InternalNo FROM member WHERE InternalNo LIKE :search";
			try {
				$stmt = $this->db->prepare($sql);
				$searchValue = trim($search);
				$stmt->bindParam(':search', $searchValue, PDO::PARAM_STR);
				$stmt->execute();
				$result = $stmt->fetch(PDO::FETCH_ASSOC);
			} catch (PDOException $exception) {
				error_log('skywin_sql_error: ' . json_encode($exception->getMessage()));
				$result = new WP_Error('skywin_sql_error', json_encode($exception->getMessage()));
			}
			return $result;
		}
		public function groups($search = NULL, $inUse = 'Y'){
			if( is_wp_error($this->db) ) return [];
			$sql = "SELECT GroupNo, GroupName FROM `group` WHERE InUse LIKE :inUse AND (GroupNo LIKE :search OR GroupName LIKE :search) ORDER BY GroupName";
			$results = [];
			try {
				$stmt = $this->db->prepare($sql);
				$searchValue = "%{$search}%";
				$stmt->bindParam(':inUse', $inUse, PDO::PARAM_STR);
				$stmt->bindParam(':search', $searchValue, PDO::PARAM_STR);
				$stmt->execute();
				$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
			} catch (PDOException $exception) {
				error_log('skywin_sql_error: ' . json_encode($exception->getMessage()));
			}
			return $results;
		}
		public function accounts($search = NULL)
		{
			if( is_wp_error($this->db) || !$search ) return [];
			$results = [];
			$sql = "SELECT
				FirstName,
				LastName,
				MemberNo,
				Emailaddress,
				Club,
				AccountNo
			FROM member
			WHERE FirstName LIKE :fname
			OR LastName LIKE :lname
			OR MemberNo LIKE :member
			OR Emailaddress LIKE :email
			OR CONCAT(Firstname, ' ', Lastname) LIKE :names
			ORDER BY LastUpd DESC LIMIT 5";
			try {
				$searchValue = "%{$search}%";
				$stmt = $this->db->prepare($sql);
				$stmt->bindParam(':fname', $searchValue, PDO::PARAM_STR);
				$stmt->bindParam(':lname', $searchValue, PDO::PARAM_STR);
				$stmt->bindParam(':member', $searchValue, PDO::PARAM_STR);
				$stmt->bindParam(':email', $searchValue, PDO::PARAM_STR);
				$stmt->bindParam(':names', $searchValue, PDO::PARAM_STR);
				$stmt->execute();
				$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
			} catch (PDOException $exception) {
				error_log('skywin_sql_error: ' . json_encode($exception->getMessage()));
			}
			return $results;
		}
		public function groups_and_accounts($search = NULL, $inUse = 'Y')
		{
			$results = [];
			if( is_wp_error($this->db) || !$search ) return $results;
			$accounts = $this->accounts($search);
			$groups = $this->groups($search, 'Y');

			$results = array_merge($accounts, $groups);

			return $results;
		}
		public function get_account_by_id($id = null)
		{
			if ( !$id || is_wp_error($this->db) ) return [];
			$sql = "SELECT * FROM member WHERE InternalNo LIKE :id";
			$result = [];
			try {
				$stmt = $this->db->prepare($sql);
				$stmt->bindParam(':id', $id, PDO::PARAM_STR);
				$stmt->execute();
				$result = $stmt->fetch(PDO::FETCH_ASSOC);
				$phone = $this->memberphone($result['InternalNo'], 'One');
				$result['PhoneNo'] = $phone['PhoneNo'] ?? '';
			} catch (PDOException $exception) {
				error_log('skywin_sql_error: ' . json_encode($exception->getMessage()));
			}
			return $result;
		}
		public function get_account_by_email($search = NULL)
		{
			if( is_wp_error($this->db) || !$search ) return [];
			$sql = "SELECT * FROM member WHERE Emailaddress LIKE :email ORDER BY LastUpd DESC LIMIT 1";
			$result = [];
			try {
				$stmt = $this->db->prepare($sql);
				$searchValue = trim($search);
				$stmt->bindParam(':email', $searchValue, PDO::PARAM_STR);
				$stmt->execute();
				$result = $stmt->fetch(PDO::FETCH_ASSOC);
				if(isset($result['InternalNo']) && !empty($result['InternalNo'])){
					$phone = $this->memberphone($result['InternalNo'], 'One');
					$result['PhoneNo'] = $phone['PhoneNo'] ?? '';
				}
				
			} catch (PDOException $exception) {
				error_log('skywin_sql_error: ' . json_encode($exception->getMessage()));
			}
			return $result;
		}
		public function get_account_by_memberno($search = NULL)
		{
			if( is_wp_error($this->db) || !$search ) return [];
			$sql = "SELECT * FROM member WHERE MemberNo LIKE :member OR ExternalMemberNo LIKE :emember";
			$results = [];
			try {
				$stmt = $this->db->prepare($sql);
				$searchValue = "%{$search}%";
				$stmt->bindParam(':member', $searchValue, PDO::PARAM_STR);
				$stmt->bindParam(':emember', $searchValue, PDO::PARAM_STR);
				$stmt->execute();
				$results = $stmt->fetch(PDO::FETCH_ASSOC);
				foreach($results as $key => $result){
					if( isset($result['InternalNo']) && !empty($result['InternalNo']) ){
						$phone = $this->memberphone($result['InternalNo'], 'One');
						$results[$key]['PhoneNo'] = $phone['PhoneNo'] ?? '';
					}
				}
			} catch (PDOException $exception) {
				error_log('SQLError: ' . json_encode($exception->getMessage()));
			}
			return $results;
		}
		public function get_transactions_by_accountNo($accountNo = NULL, $perPage = NULL, $offset = NULL)
		{
			$conn = $this->db_connect();
			$sql = "SELECT * FROM trans WHERE AccountNo LIKE ?";
			try {
				$accountNo = esc_sql($accountNo);
				$stmt = $conn->prepare($sql);
				$stmt->execute(array($accountNo));
				$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
			} catch (PDOException $exception) {
				error_log('SQLError: ' . json_encode($exception->getMessage()));
				return new WP_Error('SQLError', json_encode($exception->getMessage()));
			}
			return $results;
		}
		public function get_inttypepayments($InUse = 'Y')
		{
			if( is_wp_error($this->db) ) return [];
			$sql = "SELECT PaymentType FROM inttypepayments WHERE InUse = $InUse ORDER BY PaymentTypeOrder";
			try {
				$stmt = $this->db->prepare($sql);
				$stmt->execute();
				$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
			} catch (PDOException $exception) {
				error_log('Get paymentTypes Error: ' . json_encode($exception->getMessage()));
				$results = [];
			}
			return $results;
		}
		public function get_typepayments($InUse = 'Y')
		{
			if( is_wp_error($this->db) ) return [];
			$sql = "SELECT PaymentType FROM typepayments WHERE InUse = $InUse ORDER BY PaymentTypeOrder";
			try {
				$stmt = $this->db->prepare($sql);
				$stmt->execute();
				$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
			} catch (PDOException $exception) {
				error_log('Get TypePayments Error: ' . json_encode($exception->getMessage()));
				$results = [];
			}
			return $results;
		}
		public function get_typecountries()
		{
			if( is_wp_error($this->db) ) return [];
			$sql = "SELECT CountryCode, CountryName, CountryCodeOrder FROM typecountries ORDER BY CountryCodeOrder, CountryName";
			try {
				$stmt = $this->db->prepare($sql);
				$stmt->execute();
				$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
			} catch (PDOException $exception) {
				error_log('skywin_sql_error: ' . json_encode($exception->getMessage()));
				$results = [];
			}
			return $results;
		}
		public function get_membertypes()
		{
			$sql = "SELECT MemberType, MemberTypename, MemberTypeorder FROM typemembers ORDER BY MemberTypeorder";
			try {
				$stmt = $this->db->prepare($sql);
				$stmt->execute();
				$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
			} catch (PDOException $exception) {
				error_log('skywin_sql_error: ' . json_encode($exception->getMessage()));
				$results = [];
			}
			return $results;
		}
		public function get_typelicenses()
		{
			if( is_wp_error($this->db) ) return [];
			$sql = "SELECT LicenseType, LicenseTypename FROM typelicenses ORDER BY LicenseTypeorder, LicenseTypename";
			try {
				$stmt = $this->db->prepare($sql);
				$stmt->execute(array());
				$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
			} catch (PDOException $exception) {
				error_log('skywin_sql_error: ' . json_encode($exception->getMessage()));
				$results = [];
			}

			return $results;
		}
		public function get_typeinstructors()
		{
			if( is_wp_error($this->db) ) return [];
			$sql = "SELECT InstructType, InstructTypename, InstructTypeorder FROM typeinstructors ORDER BY InstructTypeorder, InstructTypename";
			try {
				$stmt = $this->db->prepare($sql);
				$stmt->execute(array());
				$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
			} catch (PDOException $exception) {
				error_log('skywin_sql_error: ' . json_encode($exception->getMessage()));
				$results = [];
			}
			return $results;
		}
		public function get_typecertificates()
		{
			if( is_wp_error($this->db) ) return [];
			$sql = "SELECT CertificateType, CertTypename, CertTypeorder FROM typecertificates ORDER BY CertTypeorder, CertTypename";
			try {
				$stmt = $this->db->prepare($sql);
				$stmt->execute(array());
				$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
			} catch (PDOException $exception) {
				error_log('skywin_sql_error: ' . json_encode($exception->getMessage()));
				$results = [];
			}
			return $results;
		}
		public function get_typephones()
		{
			if( is_wp_error($this->db) ) return [];
			$sql = "SELECT PhoneType, PhoneTypename FROM typephones ORDER BY PhoneTypeorder, PhoneTypename";
			try {
				$stmt = $this->db->prepare($sql);
				$stmt->execute(array());
				$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
			} catch (PDOException $exception) {
				error_log('skywin_sql_error: ' . json_encode($exception->getMessage()));
				$results = [];
			}
			return $results;
		}
		public function get_jumplog($id = NULL)
		{
			if( is_wp_error($this->db) || !$id ) return [];
			$sql = "SELECT * FROM loadjump WHERE InternalNo LIKE ? ORDER BY TimeForInsert";
			try {
				$stmt = $this->db->prepare($sql);
				$stmt->execute(array($id));
				$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
			} catch (PDOException $exception) {
				error_log('skywin_sql_error: ' . json_encode($exception));
				$results = [];
			}
			return $results;
		}
		public function get_typejumps()
		{
			if( is_wp_error($this->db) ) return [];
			$sql = "SELECT * FROM typejumps";
			try {
				$stmt = $this->db->prepare($sql);
				$stmt->execute(array());
				$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
			} catch (PDOException $exception) {
				error_log('skywin_sql_error: ' . json_encode($exception));
				$results = [];
			}

			return $results;
		}
		public function get_balance_by_internalNo($search = NULL)
		{
			if( is_wp_error($this->db) || !$search ) return [];
			try {
				$sql = "SELECT Balance FROM member WHERE InternalNo LIKE :search";
				$stmt = $this->db->prepare($sql);
				$stmt->bindParam(':search', $search);
				$stmt->execute();
				$result = $stmt->fetch(PDO::FETCH_ASSOC);
			} catch (PDOException $exception) {
				error_log('skywin_sql_error: ' . json_encode($exception) );
				$result = [];
			}
			return $result;
		}
		public function get_wishlist($search = NULL)
		{
			if( is_wp_error($this->db) || !$search ) return [];
			try {
				$stmt = $this->db->prepare('SELECT * FROM loadjumprequest AS ljr LEFT JOIN member AS m ON m.InternalNo = ljr.InternalNo');
				$stmt->execute();
				$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
			} catch (PDOException $exception) {
				error_log('skywin_sql_error: ' . json_encode($exception) );
				$results = [];
			}
			return $results;
		}
	}
	function skywin_hub_db()
	{
		return Skywin_Hub_DB::instance();
	}
endif;
