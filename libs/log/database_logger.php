<?php
if (!class_exists('ClassRegistry')) {
	App::import('Core', 'ClassRegistry');
}
class DatabaseLogger extends Object {

	var $db = null;

	var $model = null;

	var $defaults = array(
		'modelName' => 'DatabaseLogger.Log',
		'datasource' => 'database_logger',
		'table' => 'logs',
	);

/**
 * Constructs a new DataBase Logger.
 * 
 * Options
 *
 * - `modelName` the name of model to instantiate.
 *
 * @param array $options Options for the DataBaseLog, see above.
 * @return void
 */
	function DatabaseLogger($options = array()) {
		$this->options = array_merge($this->defaults, (array) $options);

		if (!isset($this->options['modelName'])) {
			throw new InvalidArgumentException("Invalid modelname");
		}

		$this->model = ClassRegistry::init(array(
			'class' => $this->options['modelName'],
			'ds' => $this->options['datasource'],
			'table' => $this->options['table'],
		));
	}

/**
 * Implements writing to log files.
 *
 * @param string $type The type of log you are making.
 * @param string $message The message you want to log.
 * @return boolean success of write.
 */
	function write($type, $message) {
		if (is_array($message)) {
			$message = json_encode($message);
		}

		$ip = env('REMOTE_ADDR');
		$browser = env('HTTP_USER_AGENT');
		$hostname = env('HTTP_HOST');
		$url = env('REQUEST_URI');
		$refer = env('HTTP_REFERER');

		$data = compact('type', 'message', 'ip', 'browser', 'hostname', 'url', 'referer');
		if (class_exists('Authsome')) {
			try {
				$data['user_id'] = Authsome::get('id');
			} catch (Exception $e) {
			}
		}

		if (!$this->db) {
			$this->db =& $this->model->getDataSource();
		}

		$debug = $this->db->fullDebug;
		$this->db->fullDebug = false;
		$this->model->create(false);
		$result = $this->model->save(array('Log' => $data));
		$this->db->fullDebug = $debug;

		return $result;
	}

}