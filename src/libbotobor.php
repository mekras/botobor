<?php
/**
 * libBotobor
 *
 * Библиотека для защиты веб-форм от спама
 *
 * Основано на {@link http://nedbatchelder.com/text/stopbots.html}
 *
 * Считается, что форма заполнена роботом, если:
 * 1. между созданием формы и её отправкой прошло слишком мало времени
 *    (см. опцию {@link Botobor_Form::__construct() delay});
 * 2. между созданием формы и её отправкой прошло слишком много времени
 *    (см. опцию {@link Botobor_Form::__construct() lifetime});
 * 3. заполнено хотя бы одно поле-приманка (см. опцию {@link Botobor_Form::__construct() honeypots}
 *    или {@link Botobor_Form::setHoneypot()}).
 *
 * <b>ИСПОЛЬЗОВАНИЕ</b>
 *
 * <b>Простой пример</b>
 *
 * Файл, создающий форму:
 * <code>
 * require 'libbotobor.php';
 * …
 * // Получите разметку формы тем способом, который предусмотрен у вас в проекте, например:
 * $html = $from->getHTML();
 * // Создайте объект-обёртку:
 * $bform = new Botobor_Form_HTML($html);
 * // Получите новую разметку формы
 * $html = $bform->getCode();
 * </code>
 *
 * Файл, обрабатывающий форму:
 * <code>
 * require 'libbotobor.php';
 * …
 * if (Botobor_Validator::isHuman())
 * {
 * 	// Форма отправлена человеком, можно обрабатывать её.
 * }
 * </code>
 *
 * <b>Пример с опциями</b>
 *
 * Можно менять поведение Botobor при помощи опций. Например, для форм комментариев имеет смысл
 * увеличить параметр lifetime, т. к. посетители перед комментированием могут долго читать статью.
 * Это можно сделать так:
 *
 * <code>
 * $bform = new Botobor_Form_HTML($html, array('lifetime' => 60)); // 60 минут
 * </code>
 *
 * Подробнее об опциях см. {@link Botobor_Form::__construct()}.
 *
 * <b>Пример с приманкой</b>
 *
 * Поля-примнаки предназначены для отлова роботов-пауков, которые находят формы самостоятельно.
 * Такие роботы, как правило, ищут в форме знакомые поля (например, name) и заполняют их. Ботобор
 * может добавить в форму скрытые от человека (при помощи CSS) поля с такими именами. Человек
 * оставит эти поля пустыми (т. к. просто не увидит), а робот заполнит и тем самым выдаст себя.
 *
 * В этом примере поле «name» будет сделано приманкой. При этом имя настоящего поля «name» будет
 * заменено на случайное значение. Обратное преобразование будет сделано во время вызова
 * {@link Botobor_Validator::isHuman()}.
 * <code>
 * $bform = new Botobor_Form_HTML($html);
 * $bform->setHoneypot('name');
 * </code>
 *
 * Подробнее об опциях см. {@link Botobor_Form::__construct()}.
 *
 * <b>ЛИЦЕНЗИЯ</b>
 *
 * Данная программа является свободным программным обеспечением. Вы
 * вправе распространять ее и/или модифицировать в соответствии с
 * условиями версии 3 либо (по вашему выбору) с условиями более поздней
 * версии Стандартной Общественной Лицензии GNU, опубликованной Free
 * Software Foundation.
 *
 * Мы распространяем эту программу в надежде на то, что она будет вам
 * полезной, однако НЕ ПРЕДОСТАВЛЯЕМ НА НЕЕ НИКАКИХ ГАРАНТИЙ, в том
 * числе ГАРАНТИИ ТОВАРНОГО СОСТОЯНИЯ ПРИ ПРОДАЖЕ и ПРИГОДНОСТИ ДЛЯ
 * ИСПОЛЬЗОВАНИЯ В КОНКРЕТНЫХ ЦЕЛЯХ. Для получения более подробной
 * информации ознакомьтесь со Стандартной Общественной Лицензией GNU.
 *
 * Вы должны были получить копию Стандартной Общественной Лицензии
 * GNU с этой программой. Если Вы ее не получили, смотрите документ на
 * <http://www.gnu.org/licenses/>
 *
 * @version 0.1.0
 * @copyright 2008-2011, Михаил Красильников, <mihalych@vsepofigu.ru>
 * @license http://www.gnu.org/licenses/gpl.txt  GPL License 3
 * @author Михаил Красильников, <mihalych@vsepofigu.ru>
 * @package Botobor
 *
 * $Id$
 */



/**
 * Вспомогательный класс
 *
 * {@link libbotobor.php Описание и примеры.}
 *
 * @package Botobor
 */
class Botobor
{
	/**
	 * Секретная строка для подписывания мета-данных
	 *
	 * @var string
	 */
	private static $secret = null;

	/**
	 * Значения по умолчанию опций защиты форм
	 *
	 * @var array
	 */
	private static $defaults = array(
		// Наименьшая задержка в секундах допустимая между созаднием и получением формы
		'delay' => 5,
		// Наибольшая задержка в минутах допустимая между созаднием и получением формы
		'lifetime' => 30,
		// Имена для приманок
		'honeypotNames' => array(
			'name', 'mail', 'email'
		),
	);

	/**
	 * Возвращает секретный ключ для подписывания мета-данных
	 *
	 * Ключ может быть задан при помощи {@link setSecret()}, в противном случае в качестве
	 * ключа будет использовано время последнего изменения файла библиотеки.
	 *
	 * @return string
	 */
	public static function secret()
	{
		$secret = self::$secret ? self::$secret : filemtime(__FILE__);
		return $secret;
	}
	//-----------------------------------------------------------------------------

	/**
	 * Возвращает подпись для указанных данных
	 *
	 * @param string $data  подписываемые данные
	 *
	 * @return string  подпись
	 *
	 * @see setSecret()
	 */
	public static function signature($data)
	{
		$signature = md5($data . self::secret());
		return $signature;
	}
	//-----------------------------------------------------------------------------

	/**
	 * Подписывает данные
	 *
	 * @param string $data  данные
	 *
	 * @return string  подписанные данные
	 *
	 * @see signature()
	 * @see setSecret()
	 */
	public static function sign($data)
	{
		$result = $data . self::signature($data);
		return $result;
	}
	//-----------------------------------------------------------------------------

	/**
	 * Проверяет достоверность данных и в случае успеха возвращает данные без подписи
	 *
	 * @param string $data  подписанные данные (данные + подпись)
	 *
	 * @return string|false  данные или false, если достоверность данных не подтверждена
	 */
	public static function verify($data)
	{
		$signature = substr($data, -32);
		$data = substr($data, 0, -32);
		$test = self::signature($data);
		return $signature == $test ? $data : false;
	}
	//-----------------------------------------------------------------------------

	/**
	 * Возвращает значение по умолчанию для опции защиты
	 *
	 * @param string $option  имя опции
	 *
	 * @return mixed
	 *
	 * @see setDefault()
	 */
	public static function getDefault($option)
	{
		return isset(self::$defaults[$option]) ? self::$defaults[$option] : null;
	}
	//-----------------------------------------------------------------------------

	/**
	 * Устанавливает значение по умолчанию для опции защиты
	 *
	 * @param string $option  имя опции
	 * @param mixed  $value   новое значение
	 *
	 * @return void
	 *
	 * @see getDefault()
	 */
	public static function setDefault($option, $value)
	{
		if (isset(self::$defaults[$option]))
		{
			self::$defaults[$option] = $value;
		}
	}
	//-----------------------------------------------------------------------------

	/**
	 * Устанавливает секретный ключ для подписывания мета-данных
	 *
	 * @param string $secret  ключ
	 *
	 * @return void
	 *
	 * @see secret()
	 * @see signature()
	 * @see sign()
	 */
	public static function setSecret($secret)
	{
		self::$secret = $secret;
	}
	//-----------------------------------------------------------------------------

}


/**
 * Абстрактная обёртка для защиты веб-формы
 *
 * Используйте дочерние классы для работы защиты форм.
 *
 * {@link libbotobor.php Описание и примеры.}
 *
 * @package Botobor
 */
abstract class Botobor_Form
{
	/**
	 * Защищаемая форма
	 *
	 * @var mixed
	 */
	protected $form;

	/**
	 * Наименьшая задержка в секундах допустимая между созаднием и получением формы
	 *
	 * @var int
	 */
	protected $delay;

	/**
	 * Наибольшая задержка в минутах допустимая между созаднием и получением формы
	 *
	 * @var int
	 */
	protected $lifetime;

	/**
	 * Приманки
	 *
	 * @var array
	 */
	protected $honeypots = array();

	/**
	 * Мета-данные формы
	 */
	protected $meta = array(
		'aliases' => array(),
	);

	/**
	 * Конструктор
	 *
	 * Аргумент $form должен содержать разметку формы в формате, предусмотренном конкретным потомком
	 * этого класса.
	 *
	 * Допустимые опции:
	 * - delay — наименьшая задержка в секундах допустимая между созаднием и получением формы
	 * - lifetime — наибольшая задержка в минутах допустимая между созаднием и получением формы
	 * - honeypot — массив имён для полей-приманок
	 *
	 * Разработчики могут перекрыть метод {@link setOptions()} для самостоятельной обработки аргумента
	 * $options.
	 *
	 * @param mixed $form     разметка формы
	 * @param array $options  ассоциативный массив опций защиты
	 *
	 * @return Botobor_Form
	 */
	public function __construct($form, array $options = null)
	{
		$this->form = $form;
		$this->delay = Botobor::getDefault('delay');
		$this->lifetime = Botobor::getDefault('lifetime');
		$this->setOptions($options);
	}
	//-----------------------------------------------------------------------------

	/**
	 * Определяет имя поля-приманки
	 *
	 * @param string $name  имя поля ввода, которое надо заменить приманкой, или 'auto'
	 *
	 * @return void
	 */
	public function setHoneypot($name)
	{
		$this->honeypots []= $name;
	}
	//-----------------------------------------------------------------------------

	/**
	 * Метод должен возвращать изменённый код формы
	 *
	 * Используйте {@link prepareMetaData()} для подготовки {@link $meta}.
	 * Используйте {@link encodeMetaData()} для кодирования {@link $meta} в строку.
	 *
	 * @return mixed
	 */
	abstract public function getCode();
	//-----------------------------------------------------------------------------

	/**
	 * Устанавливает значения опций из переданного массива
	 *
	 * @param array $options
	 *
	 * @return void
	 */
	protected function setOptions(array $options = null)
	{
		if ($options)
		{
			foreach ($options as $key => $value)
			{
				if (property_exists($this, $key))
				{
					$this->$key = $value;
				}
			}
		}
	}
	//-----------------------------------------------------------------------------

	/**
	 * Подготавливает мета-данные
	 *
	 * @return void
	 */
	protected function prepareMetaData()
	{
		$this->meta['timestamp'] = time();
		$this->meta['delay'] = $this->delay;
		$this->meta['lifetime'] = $this->lifetime;

		if (isset($_SERVER['REQUEST_URI']) && $_SERVER['HTTP_HOST'])
		{
			$this->meta['referer'] = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ? 'https' : 'http';
			$this->meta['referer'] .= '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		}
	}
	//-----------------------------------------------------------------------------

	/**
	 * Кодирует мета-данные
	 *
	 * @return string  закодированные данные
	 */
	protected function encodeMetaData()
	{
		$data = serialize($this->meta);
		if (function_exists('gzdeflate'))
		{
			$data = gzdeflate($data);
		}
		$data = base64_encode($data);
		$data = Botobor::sign($data);
		return $data;
	}
	//-----------------------------------------------------------------------------
}

/**
 * Обёртка для защиты HTML-формы
 *
 * <code>
 * $bform = new Botobor_Form_HTML('<form …');
 * $html = $bform->getCode();
 * </code>
 *
 * {@link libbotobor.php Описание и примеры.}
 *
 * @package Botobor
 * @since 0.1.0
 */
class Botobor_Form_HTML extends Botobor_Form
{
	/**
	 * Возвращает разметку защищённой формы
	 *
	 * @return string  HTML
	 */
	public function getCode()
	{
		$this->prepareMetaData();

		$html = $this->createHoneypots($this->form, $this->honeypots);

		$botoborData =
			'<div style="display: none;">' .
			$this->createInput('hidden', 'botobor_meta_data', $this->encodeMetaData()) .
			'</div>';

		$html = preg_replace('/(<form[^>]*>)/si', '$1'.$botoborData, $html);

		return $html;
	}
	//-----------------------------------------------------------------------------

	/**
	 * Создаёт разметку тега <input>
	 *
	 * @param string $type   значение для атрибута type
	 * @param string $name   значение для атрибута name
	 * @param string $value  значение для атрибута value
	 * @param string $extra  дополнительные атрибуты
	 *
	 * @return string
	 *
	 * @since 0.1.0
	 */
	protected function createInput($type, $name, $value = null, $extra = null)
	{
		$html = '<input type="' . $type . '" name="' . $name . '"';
		if ($value !== null)
		{
			$html .= ' value="' . $value . '"';
		}
		if ($extra !== null)
		{
			$html .= ' ' . $extra;
		}
		$html .= '>';
		return $html;
	}
	//-----------------------------------------------------------------------------

	/**
	 * Заменяет поля ввода полями-приманками
	 *
	 * @param string $html   HTML-разметка формы
	 * @param array  $names  Массив имён полей
	 *
	 * @return void
	 */
	protected function createHoneypots($html, array $names)
	{
		$honeypots = '';
		foreach ($names as $name)
		{
			$p = strpos($html, 'name="' . $name . '"');
			if ($p === false)
			{
				continue;
			}

			$honeypots .= $this->createInput('text', $name);

			$alias = '';
			$length = mt_rand(8, 15);
			for ($i = 0; $i < $length; $i++)
			{
				$alias .= chr(mt_rand(ord('a'), ord('z')));
			}

			$this->meta['aliases'][$alias] = $name;

			$html = substr_replace($html, $alias, $p + 6, strlen($name));
		}

		if ($honeypots != '')
		{
			$html = preg_replace('/(<form[^>]*>)/si',
				'$1<div style="display: none;">' . $honeypots . '</div>', $html);
		}

		return $html;
	}
	//-----------------------------------------------------------------------------
}



/**
 * Класс проверки принятой формы
 *
 * Проверяет форму, принятую в обрабатываемом запросе. При этом проводится обратная замена
 * полей-приманок на исходные поля формы.
 *
 * {@link libbotobor.php Описание и примеры.}
 *
 * @package Botobor
 */
class Botobor_Keeper
{
	/**
	 * Возвращает true если форму отправил человек
	 *
	 * @return bool
	 */
	public static function isHuman()
	{
		$meta = self::importMetaData('botobor_meta_data');

		if (!$meta)
		{
			return false;
		}

		if (time() - $meta['timestamp'] < $meta['delay'])
		{
			return false;
		}

		if (time() - $meta['timestamp'] > $meta['lifetime'] * 60)
		{
			return false;
		}

		if (
			isset($meta['referer']) &&
			(!isset($_SERVER['HTTP_REFERER']) || $meta['referer'] != $_SERVER['HTTP_REFERER'])
			)
		{
			return false;
		}

		if (strcasecmp($_SERVER['REQUEST_METHOD'], 'POST') === 0)
		{
			$req =& $_POST;
		}
		else
		{
			$req =& $_GET;
		}

		if ($meta['aliases'])
		{
			foreach ($meta['aliases'] as $alias => $name)
			{
				if (isset($req[$name]) && $req[$name])
				{
					return false;
				}

				$req[$name] = $req[$alias];
				unset($req[$alias]);
			}
		}

		return true;
	}
	//-----------------------------------------------------------------------------

	/**
	 * Извлекает мета-данные из запроса
	 *
	 * @param string $name  имя аргумента запроса, хранящего мета-данные
	 *
	 * @return array
	 */
	private static function importMetaData($name)
	{
		switch (strtolower(@$_SERVER['REQUEST_METHOD']))
		{
			case 'get':
				$data = @$_GET[$name];
			break;

			case 'post':
				$data = @$_POST[$name];
			break;

			default:
				$data = null;
		}
		if ($data)
		{
			$data = Botobor::verify($data);
			if (!$data)
			{
				return null;
			}

			$data = base64_decode($data);
			if (function_exists('gzinflate'))
			{
				$data = gzinflate($data);
			}
			$data = unserialize($data);
		}
		return $data;
	}
	//-----------------------------------------------------------------------------

}