<?php

class WideImageThumb {
	
	// For create self config make thumb.php in config folder (only for Laravel 4)

	static $default_config = array(
		'format'        => array(
			'default'  => array(
				'size' => array(250, 250),
				'true' => true
			),
			'listing'  => array(
				'size' => array(100, 100),
				'crop' => true
			),
			'lightbox' => array(
				'size' => array(1000, 1000),
			),
		),
		'defaultFormat' => 'default',
		'max_size'      => array(
			'weight' => 8,      // Mb
			'h'      => 3000,   // Px
			'w'      => 3000,   // Px
		),
		'path_cache'    => '/cache',
		'empty_image'   => '/images/empty_image.png',
	);

	static private $instance = null;
	static private $formatName = null;

	private $original = null;
	private $url_cache = null;

	static public function factory($original, $format = 'default') {
		self::$formatName = $format;
		self::$instance   = new self($original, $format);

		return self::$instance;
	}

	public function __construct($original) {
		$this->original = $original;

		if (!is_file(public_path() . $this->original)) {
			return $this;
		}

		if (filesize(public_path() . $this->original) > self::config('max_size.weight') * 1024 * 1024) {
			return $this;
		}

		$cache_dir = self::config('format.path_cache') . dirname($this->original) . '/' . self::$formatName . '/';

		if (!is_dir(public_path() . $cache_dir)) {
			mkdir(public_path() . $cache_dir, 0777, true);
		}

		$cache_filename = md5($this->original) . '.' . pathinfo(public_path() . $this->original, PATHINFO_EXTENSION);
		$cache_path     = public_path() . $cache_dir . $cache_filename;

		if (!is_file($cache_path)) {
			try {
				$this->generate($cache_path);
			} catch (Exception $e) {
				Log::error('WideImageThumb / error generate Thumb');
			}
		}

		$this->url_cache = $cache_dir . $cache_filename;

		return $this;
	}

	static private function config($key) {
		if (Config::get('thumb')) {
			return Config::get('thumb.' . $key);
		}
		return array_get(self::$default_config, $key);
	}

	private function generate($cache_path) {
		$format = self::config('format.' . self::$formatName);

		$info = getimagesize(public_path() . $this->original);

		if ($info[0] > self::config('max_size.w') or $info[1] > self::config('max_size.h')) {
			return $this;
		}

		$image = WideImage\WideImage::load(public_path() . $this->original);

		if (isset($format['crop']) and $format['crop']) {
			$image = $image->resize($format['size'][0], $format['size'][1], 'outside', 'down')->crop('center', 'center', $format['size'][0], $format['size'][1]);
		} else {
			$image = $image->resize($format['size'][0], $format['size'][1], 'inside', 'down');
		}

		$image->saveToFile($cache_path);
	}

	public function getUrlCache() {
		return $this->url_cache;
	}

	public function get() {
		$result = $this->getUrlCache();
		if (!$result) {
			$result = self::factory(self::config('empty_image'), self::$formatName)->get();
		}

		return $result;
	}

}