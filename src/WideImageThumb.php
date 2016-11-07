<?php

class WideImageThumb {

	static $format = array(
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
	);

	static private $max_size_Mb = 8;
	static private $instance = null;
	static private $defaultFormat = 'default';
	static private $formatName = null;

	private $url_cache = null;

	static public function factory($original, $format = 'default') {
		self::$formatName = $format;
		self::$instance = new self($original, $format);

		return self::$instance;
	}

	public function __construct($original) {
		$format = self::$format[self::$formatName];
		if (!is_file(public_path() . $original)) {
			return $this;
		}
		if(filesize(public_path() . $original) > Config::get('app.max_upload_size')){
			return null;
		}
		$cache = '/cache' . dirname($original) . '/' . self::$formatName . '/';
		if (!is_dir(public_path() . $cache)) {
			mkdir(public_path() . $cache, 0777, true);
		}

		$cache_filename = md5($original) . '.' . pathinfo(public_path() . $original, PATHINFO_EXTENSION);

		if (!is_file(public_path() . $cache . $cache_filename)) {
			try {
				$info = getimagesize(public_path() . $original);
				if($info[0] > 3000 or $info[1] > 3000){
					return $this;
				}
				$image = WideImage::load(public_path() . $original);
				if (isset($format['crop']) and $format['crop']) {
									
					$image = $image->resize($format['size'][0], $format['size'][1], 'outside', 'down')->crop('center', 'center', $format['size'][0], $format['size'][1]);

				} else {
					$image = $image->resize($format['size'][0], $format['size'][1], 'inside', 'down');
				}
				$image->saveToFile(public_path() . $cache . $cache_filename);
			} catch (Exception $e) {

			}
			//			Mews\Thumb\Facades\Thumb::create(public_path() . $original)->make('resize', $format['size'])->save(public_path() . $cache, $cache_filename);
		}

		$this->url_cache = $cache . $cache_filename;

		return $this;
	}

	private function getUrlCache() {
		return $this->url_cache;
	}

	public function get() {
		$result = $this->getUrlCache();
		if(!$result){
			$result = Thumb::factory('/images/empty_image.png', self::$formatName)->get();
		}
		return $result;
	}

}