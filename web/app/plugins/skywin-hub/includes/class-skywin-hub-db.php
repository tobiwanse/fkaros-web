<?php
defined('ABSPATH') || exit;
if ( !class_exists('Skywin_Hub_DB') ) :
class Skywin_Hub_DB {
	protected static $_instance = null;
	private $connection = null;
	private $option_page;
	private $db_host;
	private $db_name;
	private $db_username;
	private $db_port;
	private $db_password;

	public static function instance() {
		if ( is_null(self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	private function db_connect() {
		if (is_null($this->db_host)) {
			$this->db_host = get_option("skywin_hub_db_host");
			$this->db_name = get_option("skywin_hub_db_name");
			$this->db_username = get_option("skywin_hub_db_username");
			$this->db_port = get_option("skywin_hub_db_port");
			$this->db_password = SKYWIN_HUB()->decrypt(get_option("skywin_hub_db_password"));
		}
		$host = $this->db_host;
		$db_name = $this->db_name;
		$username = $this->db_username;
		$port = $this->db_port;
		$password = $this->db_password;
		if (!$host || !$db_name || !$username || !$port || !$password) {
			return new WP_Error('error', __('Db Credentials can not be empty.', 'skywin_hub'));
		}
		try{
			$this->connection = new PDO("mysql:host=" . $host . ";dbname=" . $db_name .";port=" . $port, 
			$username, $password, array(
				PDO::ATTR_TIMEOUT => 10,
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_PERSISTENT => true,
			));
			$this->connection->exec("set names utf8");
		} catch (PDOException $exception) {
			error_log('PDOError (1): ' . json_encode( $exception->getMessage() ) );
			return new WP_Error( 'sqlerr',  $exception->getMessage() );
		}
		if ( is_array( $this->connection ) && array_key_exists('error', $this->connection) ) {
			error_log('PDOError (2): ' . json_encode( $this->connection ) );			
			return new WP_Error( 'sqlerr', json_encode($this->connection) );
		}
		if(!$this->connection || !is_object($this->connection) || !method_exists($this->connection, 'prepare') ){
			error_log('PDOError (3): ' . json_encode( $this->connection ) );
			return new WP_Error( 'sqlerr', json_encode($this->connection) );
		}
		
		return $this->connection;
	}
	public function status(){
		$results = null;

		$conn = $this->db_connect();

		if( is_wp_error($conn) ){
			return $conn;
		}

		$sql = "SELECT 1";

		try { 
			$stmt = $conn->prepare( $sql );
			$stmt->execute( array() );
			$results = $stmt->fetchAll( PDO::FETCH_ASSOC );
		}
		catch ( PDOException $exception ) {
			error_log(json_encode($exception->getMessage()));
			return new WP_Error( 'Status Error', json_encode($exception->getMessage()) );
		}

		return $results;
	}
	public function accounts($search = null){
		$conn = $this->db_connect();
		if( is_wp_error($conn) ){
			return $conn;
		}
		$sql = "SELECT
					m.AccountNo, m.MemberNo, m.ExternalMemberNo, m.FirstName, m.LastName, m.NickName, m.Club, m.Emailaddress, p.PhoneNo, p.PhoneType
					FROM member AS m LEFT JOIN memberphone AS p ON (m.InternalNo = p.InternalNo AND p.PhoneType = 'M')
					WHERE (m.MemberNo IS NOT NULL OR m.ExternalMemberNo IS NOT NULL)";
		try { 
			$stmt = $conn->prepare( $sql );
			$stmt->execute( array() );
			$results = $stmt->fetchAll( PDO::FETCH_ASSOC );
		}
		catch ( PDOException $exception ) {
			return new WP_Error( 'Get Accounts Error', json_encode($exception->getMessage()) );
		}
				
		return $results;
	}
	public function clubs( $InUse = NULL ){
		error_log('Skywin_Hub_DB::clubs');
		$conn = $this->db_connect();
		if( is_wp_error($conn) ){
			return $conn;
		}
		
		$sql = "SELECT Club, Name, CountryCode, Emailaddress, InUse FROM club WHERE InUse LIKE ? ORDER BY Name";
		try { 
			$InUse = esc_sql( $InUse );
			$stmt = $conn->prepare( $sql );
			$stmt->execute( array ( $InUse ) );
			$results = $stmt->fetchAll( PDO::FETCH_ASSOC );
		}
		catch ( PDOException $exception ) {
			error_log('SQLError: ' . json_encode( $exception->getMessage() ) );
			return new WP_Error( 'Get Clubs Error', json_encode( $exception->getMessage() ) );
		}
		return $results;
	}
	public function get_account_by_pid($search = NULL) {
		error_log('Skywin_Hub_DB::get_account_by_pid');
		
		$conn = $this->db_connect();
			
		if ( is_wp_error( $conn ) ) {
			return $conn;
		}
		
		$sql = "SELECT * FROM member WHERE PID LIKE ?";
		
		try { 
			$search = esc_sql($search);
			$stmt = $conn->prepare( $sql );
			$stmt->execute( array ( $search ) );
			$result = $stmt->fetch( PDO::FETCH_ASSOC );
			if($result){
				$result['PhoneNo'] = null;
				$result['PhoneType'] = null;	
				$stmt = $conn->prepare( "SELECT * FROM memberphone WHERE InternalNo LIKE :search ");
				$stmt->bindParam(':search',$result['InternalNo']);
				$stmt->execute();
				$phone = $stmt->fetch( PDO::FETCH_ASSOC );
				if( $phone){
					$result['PhoneNo'] = isset($phone['PhoneNo']) ? $phone['PhoneNo'] : null;
					$result['PhoneType'] = isset($phone['PhoneType']) ? $phone['PhoneType'] : null;
				}
			}
		}
		
		catch ( PDOException $exception ) {
			error_log('SQLError: ' . json_encode($exception->getMessage()) );
			return new WP_Error( 'SQLError', json_encode($exception->getMessage()) );
		}

		return $result;

	}
	public function get_account_by_id($id){
		error_log('Skywin_API::get_account_by_id');
		$conn = $this->db_connect();
		
		if ( is_wp_error( $conn ) ) {
			return $conn;
		}
		$sql = "SELECT * FROM member WHERE InternalNo LIKE ?";
		try { 
			$stmt = $conn->prepare( $sql );
			$stmt->execute( array ( $id ) );
			$result = $stmt->fetch( PDO::FETCH_ASSOC );
			if( isset($result['InternalNo']) && !empty($result['InternalNo']) ){
				$result['PhoneNo'] = null;
				$result['PhoneType'] = null;	
				$stmt = $conn->prepare( "SELECT * FROM memberphone WHERE InternalNo LIKE :search ");
				$stmt->bindParam(':search',$result['InternalNo']);
				$stmt->execute();
				$phone = $stmt->fetch( PDO::FETCH_ASSOC );
				if( $phone){
					$result['PhoneNo'] = isset($phone['PhoneNo']) ? $phone['PhoneNo'] : null;
					$result['PhoneType'] = isset($phone['PhoneType']) ? $phone['PhoneType'] : null;
				}
			}
		}
		
		catch ( PDOException $exception ) {
			error_log('SQLError: ' . json_encode($exception->getMessage()) );
			return new WP_Error( 'SQLError', json_encode($exception->getMessage()) );
		}
		
		return $result;
	}
	public function get_account_by_email($search = NULL){
		error_log('Skywin_Hub_DB::get_account_by_email');		
		$conn = $this->db_connect();
		if ( is_wp_error( $conn ) ) {
			return $conn;
		}
		try {
			$stmt = $conn->prepare( "SELECT * FROM member WHERE Emailaddress LIKE :search ");
			$stmt->bindParam(':search',$search);
			$stmt->execute();
			$result = $stmt->fetch( PDO::FETCH_ASSOC );
			
			if( isset($result['InternalNo']) && !empty($result['InternalNo']) ){
				$result['PhoneNo'] = null;
				$result['PhoneType'] = null;
				$stmt = $conn->prepare( "SELECT * FROM memberphone WHERE InternalNo LIKE :search ");
				$stmt->bindParam(':search',$result['InternalNo']);
				$stmt->execute();
				$phone = $stmt->fetch( PDO::FETCH_ASSOC );
				if( $phone ){
					$result['PhoneNo'] = isset($phone['PhoneNo']) ? $phone['PhoneNo'] : null;
					$result['PhoneType'] = isset($phone['PhoneType']) ? $phone['PhoneType'] : null;
				}
			}
		}
		catch ( PDOException $exception ) {
			error_log('SQLError: ' . json_encode($exception->getMessage()) );
			return new WP_Error( 'SQLError', json_encode($exception->getMessage()) );
		}
		return $result;
	}
	public function get_account_by_MemberNo($search = NULL){
		error_log('Skywin_Hub_DB::get_account_by_MemberNo');
		$conn = $this->db_connect();
		$sql = "SELECT * FROM member WHERE MemberNo = ?";
		if ( is_wp_error( $conn ) ) {
			return $conn;
		}
		try { 
			$search = esc_sql($search);
			$stmt = $conn->prepare( $sql );
			$stmt->execute( array ( $search ) );
			$result = $stmt->fetch( PDO::FETCH_ASSOC );
			if( isset($result['InternalNo']) && !empty($result['InternalNo']) ){
				$result['PhoneNo'] = null;
				$result['PhoneType'] = null;
				$stmt = $conn->prepare( "SELECT * FROM memberphone WHERE InternalNo LIKE :search ");
				$stmt->bindParam(':search',$result['InternalNo']);
				$stmt->execute();
				$phone = $stmt->fetch( PDO::FETCH_ASSOC );
				if( $phone ){
					$result['PhoneNo'] = isset($phone['PhoneNo']) ? $phone['PhoneNo'] : null;
					$result['PhoneType'] = isset($phone['PhoneType']) ? $phone['PhoneType'] : null;
				}
			}
		}
		catch ( PDOException $exception ) {
			error_log('SQLError: ' . json_encode( $exception->getMessage() ) );
			return new WP_Error( 'SQLError', json_encode($exception->getMessage()) );
		}
		return $result;
	}
	public function get_typecountries(){		
		error_log('Skywin_Hub_DB::get_countries');
				
		$conn = $this->db_connect();
			
		$sql = "SELECT CountryCode, CountryName, CountryCodeOrder FROM typecountries ORDER BY CountryCodeOrder, CountryName";	
		
		if ( is_wp_error( $conn ) ) {
			return $conn;
		}
		
		try { 
								
			$stmt = $conn->prepare( $sql );
						
			$stmt->execute();
			
			$results = $stmt->fetchAll( PDO::FETCH_ASSOC );
			
		}
		
		catch ( PDOException $exception ) {
			
			error_log('SQLError: ' . json_encode( $exception->getMessage() ) );
			
			return new WP_Error( 'SQLError', json_encode($exception->getMessage()) );
		
		}
			
		return $results;
	}
	public function get_membertypes(){
		error_log('Skywin_API::get_membertypes');
				
		$conn = $this->db_connect();
			
		$sql = "SELECT MemberType, MemberTypename, MemberTypeorder FROM typemembers ORDER BY MemberTypeorder";
		
		if ( is_wp_error( $conn ) ) {
			return $conn;
		}
		
		try { 
			$stmt = $conn->prepare( $sql );
			$stmt->execute();
			$results = $stmt->fetchAll( PDO::FETCH_ASSOC );
		}
		
		catch ( PDOException $exception ) {
			error_log('SQLError: ' . json_encode( $exception->getMessage() ) );
			return new WP_Error( 'SQLError', json_encode($exception->getMessage()) );
		}
		
		return $results;
	}
	public function get_transactions_by_accountNo( $accountNo = NULL, $perPage = NULL, $offset = NULL){
		$conn = $this->db_connect();
		$sql = "SELECT * FROM trans WHERE AccountNo LIKE ?";
		if ( is_wp_error( $conn ) ) {
			return $conn;
		}
		
		try { 
			$accountNo = esc_sql($accountNo);
			$stmt = $conn->prepare( $sql );
			$stmt->execute( array ( $accountNo ) );
			$results = $stmt->fetchAll( PDO::FETCH_ASSOC );
		}
		
		catch ( PDOException $exception ) {
			error_log('SQLError: ' . json_encode( $exception->getMessage() ) );
			return new WP_Error( 'SQLError', json_encode($exception->getMessage()) );
		}
		return $results;
	}
	public function get_typelicenses(){
		$sql = "SELECT LicenseType, LicenseTypename FROM typelicenses ORDER BY LicenseTypeorder, LicenseTypename";
				
		$conn = $this->db_connect();
			
		if ( is_wp_error( $conn  ) ) {
									
			return $conn;
		
		}
		
		try { 
					
			$stmt = $conn ->prepare( $sql );
						
			$stmt->execute( array ( ) );
			
			$results = $stmt->fetchAll( PDO::FETCH_ASSOC );
			
		}
		
		catch ( PDOException $exception ) {
			error_log('SQLError: ' . json_encode( $exception->getMessage() ) );
			return new WP_Error( 'SQLError', json_encode($exception->getMessage()) );
		}
		
		return $results;
	
	}
	public function get_typeinstructors(){
		error_log('Skywin_API::get_typeinstructors');
		$conn = $this->db_connect();
		$sql = "SELECT InstructType, InstructTypename, InstructTypeorder FROM typeinstructors ORDER BY InstructTypeorder, InstructTypename";
		if ( is_wp_error( $conn ) ) {
			return $conn;
		}
		try { 
			$stmt = $conn->prepare( $sql );
			$stmt->execute( array ( ) );
			$results = $stmt->fetchAll( PDO::FETCH_ASSOC );
		}
		catch ( PDOException $exception ) {
			error_log('SQLError: ' . json_encode( $exception->getMessage() ) );
			return new WP_Error( 'SQLError', json_encode($exception->getMessage()) );
		}
		return $results;
	}
	public function get_typecertificates(){
		error_log('Skywin_API::get_typecertificates');
		
		$conn = $this->db_connect();
		$sql = "SELECT CertificateType, CertTypename, CertTypeorder FROM typecertificates ORDER BY CertTypeorder, CertTypename";
		if ( is_wp_error( $conn ) ) {
			return $conn;
		}
		
		try { 
			$stmt = $conn->prepare( $sql );
			$stmt->execute( array ( ) );
			$results = $stmt->fetchAll( PDO::FETCH_ASSOC );
		}
		
		catch ( PDOException $exception ) {
			error_log('SQLError: ' . json_encode( $exception->getMessage() ) );
			return new WP_Error( 'SQLError', json_encode($exception->getMessage()) );
		}
		return $results;
	}
	public function get_typephones(){
		error_log('Skywin_API::get_typephones');
		
		$conn = $this->db_connect();
		$sql = "SELECT PhoneType, PhoneTypename, PhoneTypename FROM typephones ORDER BY PhoneTypeorder, PhoneTypename";
		if ( is_wp_error( $conn ) ) {
			return $conn;
		}
		
		try { 
			$stmt = $conn->prepare( $sql );
			$stmt->execute( array ( ) );
			$results = $stmt->fetchAll( PDO::FETCH_ASSOC );
		}
		
		catch ( PDOException $exception ) {
			error_log('SQLError: ' . json_encode( $exception->getMessage() ) );
			return new WP_Error( 'SQLError', json_encode($exception->getMessage()) );
		
		}
		
		return $results;
	
	}
	public function get_jumplog( $id ){
		error_log("Skywin_API::get_jumplog");
		
		$conn = $this->db_connect();
			
		$sql = "SELECT * FROM loadjump WHERE InternalNo LIKE ? ORDER BY TimeForInsert";
				
		if ( is_wp_error( $conn ) ) {
									
			return $conn;
		
		}
		
		try { 
					
			$stmt = $conn->prepare( $sql );
						
			$stmt->execute( array ( $id ) );
			
			$results = $stmt->fetchAll( PDO::FETCH_ASSOC );
			
		}
		
		catch ( PDOException $exception ) {
			
			error_log('SQLError: ' . json_encode( $exception->getMessage() ) );
			
			return new WP_Error( 'SQLError', json_encode($exception->getMessage()) );
		
		}
		
		return $results;

	}
	public function get_typejumps(){
		error_log("Skywin_API::get_typejumps");
		
		$conn = $this->db_connect();
			
		$sql = "SELECT * FROM typejumps";
				
		if ( is_wp_error( $conn ) ) {
									
			return $conn;
		
		}
		
		try { 
					
			$stmt = $conn->prepare( $sql );
						
			$stmt->execute( array () );
			
			$results = $stmt->fetchAll( PDO::FETCH_ASSOC );
			
		}
		
		catch ( PDOException $exception ) {
			
			error_log('SQLError: ' . json_encode( $exception->getMessage() ) );
			
			return new WP_Error( 'SQLError', json_encode($exception->getMessage()) );
		
		}
		
		return $results;
	
	}
	public function get_balance_by_internalNo($search = NULL){
		error_log('Skywin_API::get_balance_by_InternalNo');		
				
		$conn = $this->db_connect();
			
		if ( is_wp_error( $conn ) ) {
						
			return $conn;
		
		}
		
		try {
					
			$stmt = $conn->prepare( 'SELECT Balance FROM member WHERE InternalNo LIKE :search' );
			
			$stmt->bindParam(':search', $search);
			
			$stmt->execute();
			
			$result = $stmt->fetch( PDO::FETCH_ASSOC );
			
		}
		
		catch ( PDOException $exception ) {
			
			error_log('SQLError: ' . json_encode($exception->getMessage()) );
			
			return new WP_Error( 'SQLError', json_encode($exception->getMessage()) );
		
		}
				
		return $result;
	}
	public function get_wishlist($search = NULL){
		error_log('Skywin_Hub_DB::get_wishlist');		
		$conn = $this->db_connect();
		if (is_wp_error($conn)) {
			return $conn;
		}
		try {
			$stmt = $conn->prepare( 'SELECT * FROM loadjumprequest AS ljr LEFT JOIN member AS m ON m.InternalNo = ljr.InternalNo' );
			$stmt->execute();
			$result = $stmt->fetchAll( PDO::FETCH_ASSOC );
		}
		catch ( PDOException $exception ) {
			error_log('SQLError: ' . json_encode($exception->getMessage()) );
			return new WP_Error( 'SQLError', json_encode($exception->getMessage()) );
		}
		return $result;
	}
}
function skywin_hub_db(){
	return Skywin_Hub_DB::instance();
}
endif;