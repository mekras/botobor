<?php
/**
 * Ботобор
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
 * if (Botobor_Keeper::isHuman())
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
 * любого проверяющего метода {@link Botobor_Keeper}, например{@link Botobor_Keeper::isHuman()}.
 *
 * <code>
 * $bform = new Botobor_Form_HTML($html);
 * $bform->setHoneypot('name');
 * </code>
 *
 * Подробнее см. {@link Botobor_Keeper::handleRequest()}.
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
	 * Имя поля для мета-данных
	 *
	 * @var string
	 * @since 0.1.0
	 */
	const META_FIELD_NAME = 'botobor_meta_data';

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
		'honeypots' => array(
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
 * Мета-данные формы
 *
 * @package Botobor
 * @since 0.1.0
 */
class Botobor_MetaData
{
	/**
	 * Мета-данные
	 *
	 * @var array
	 * @see __get(), __set()
	 */
	private $data = array();

	/**
	 * Признак достоверности данных
	 *
	 * @var bool
	 * @see isValid()
	 */
	private $isValid = true;

	/**
	 * Возвращает элемент мета-данных
	 *
	 * @param string $key
	 *
	 * @return mixed
	 *
	 * @since 0.1.0
	 */
	public function __get($key)
	{
		return isset($this->data[$key]) ? $this->data[$key] : null;
	}
	//-----------------------------------------------------------------------------

	/**
	 * Устанавливает элемент мета-данных
	 *
	 * @param string $key
	 * @param mixed  $value
	 *
	 * @return void
	 *
	 * @since 0.1.0
	 */
	public function __set($key, $value)
	{
		if (is_array($value))
		{
			$value = new ArrayObject($value);
		}
		$this->data[$key] = $value;
	}
	//-----------------------------------------------------------------------------

	/**
	 * Возвращает true если данные достоверны
	 *
	 * @return bool
	 *
	 * @since 0.1.0
	 */
	public function isValid()
	{
		return $this->isValid;
	}
	//-----------------------------------------------------------------------------

	/**
	 * Кодирует мета-данные
	 *
	 * @return string  закодированные данные
	 *
	 * @since 0.1.0
	 */
	public function encode()
	{
		$data = serialize($this->data);
		if (function_exists('gzdeflate'))
		{
			$data = gzdeflate($data);
		}
		$data = base64_encode($data);
		$data = Botobor::sign($data);
		return $data;
	}
	//-----------------------------------------------------------------------------

	/**
	 * Декодирует мета-данные
	 *
	 * @param string $encoded  закодированные данные
	 *
	 * @return void
	 *
	 * @since 0.1.0
	 */
	public function decode($encoded)
	{
		$signature = substr($encoded, -32);
		$data = substr($encoded, 0, -32);
		$validSignature = Botobor::signature($data);
		$this->isValid = $signature == $validSignature;

		if ($data)
		{
			$data = base64_decode($data);
			if (function_exists('gzinflate'))
			{
				$data = gzinflate($data);
			}
			$data = unserialize($data);
			if ($data)
			{
				$this->data = $data;
			}
		}
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
	 * Мета-данные
	 *
	 * @var Botobor_MetaData
	 * @since 0.1.0
	 */
	protected $meta;

	/**
	 * Приманки
	 *
	 * @var array
	 */
	protected $honeypots = array();

	/**
	 * Конструктор
	 *
	 * Аргумент $form должен содержать разметку формы в формате, предусмотренном конкретным потомком
	 * этого класса.
	 *
	 * Допустимые опции:
	 * - delay — наименьшая задержка в секундах допустимая между созаднием и получением формы
	 * - lifetime — наибольшая задержка в минутах допустимая между созаднием и получением формы
	 *
	 * Так же эти значения можно установить через методы {@link setDelay()}, {@link setLifetime()}.
	 *
	 * @param mixed $form     разметка формы
	 * @param array $options  ассоциативный массив опций защиты
	 *
	 * @return Botobor_Form
	 */
	public function __construct($form, array $options = array())
	{
		$this->form = $form;
		$this->meta = new Botobor_MetaData();

		$this->setDelay(isset($options['delay']) ? $options['delay'] : Botobor::getDefault('delay'));
		$this->setLifetime(
			isset($options['lifetime']) ? $options['lifetime'] : Botobor::getDefault('lifetime'));
		$this->honeypots = Botobor::getDefault('honeypots');

		$this->meta->timestamp = time();

		if (isset($_SERVER['REQUEST_URI']) && $_SERVER['HTTP_HOST'])
		{
			$this->meta->referer = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ? 'https' : 'http';
			$this->meta->referer .= '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		}
	}
	//-----------------------------------------------------------------------------

	/**
	 * Устанавливает наименьшую допустимую задержку перед отправкой формы
	 *
	 * @param int $seconds  количество секунд
	 *
	 * @return void
	 */
	public function setDelay($seconds)
	{
		$this->meta->delay = $seconds;
	}
	//-----------------------------------------------------------------------------

	/**
	 * Устанавливает наибольшую допустимую задержку перед отправкой формы
	 *
	 * @param int $minutes  количество минут
	 *
	 * @return void
	 */
	public function setLifetime($minutes)
	{
		$this->meta->lifetime = $minutes;
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
	 * Используйте {@link Botobor_MetaData::encode()} для кодирования {@link $meta} в строку.
	 *
	 * @return mixed
	 */
	abstract public function getCode();
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
		$html = $this->createHoneypots($this->form, $this->honeypots);

		$botoborData =
			'<div style="display: none;">' .
			$this->createInput('hidden', Botobor::META_FIELD_NAME, $this->meta->encode()) .
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
		$this->meta->aliases = array();
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

			$this->meta->aliases[$alias] = $name;

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
 * @package Botobor
 * @link libbotobor.php Описание и примеры
 * @see isHuman()
 */
class Botobor_Keeper
{
	/**
	 * Признак того, что запрос уже обработан
	 *
	 * @var bool
	 * @since 0.1.0
	 */
	private static $isHandled = false;

	/**
	 * Признак того, что форму заполнил человек
	 *
	 * @var bool
	 * @since 0.1.0
	 */
	private static $isHuman = false;

	/**
	 * Обрабатывает текущий запрос
	 *
	 * Если в текущем запросе содержатся мета-данные Ботобора, они извлекаются и проверяются.
	 * Проводится обратная замена полей-приманок на исходные поля формы.
	 *
	 * Этот метод вызывается автоматически из {@link isHuman()}. Но разработчики могут вызывать его
	 * самостоятельно, если их приложение получает аргументы запроса не напрямую из $_GET или $_POST,
	 * а через посредничество другого класса, который извлекает аргументы до обращения к
	 * {@link isHuman()}. В этом случае надо вызвать {@link handleRequest()} до создания
	 * класса-посредника.
	 *
	 * @return void
	 *
	 * @since 0.1.0
	 */
	public static function handleRequest()
	{
		self::$isHandled = true;

		self::$isHuman = true;
		switch (strtoupper(@$_SERVER['REQUEST_METHOD']))
		{
			case 'GET':
				$req =& $_GET;
			break;

			case 'POST':
				$req =& $_POST;
			break;

			default:
				self::$isHuman = false;
				return;
		}

		if (!isset($req[Botobor::META_FIELD_NAME]))
		{
			self::$isHuman = false;
			return;
		}

		$meta = new Botobor_MetaData();
		$meta->decode($req[Botobor::META_FIELD_NAME]);

		if ($meta->aliases)
		{
			foreach ($meta->aliases as $alias => $name)
			{
				if (isset($req[$name]) && $req[$name])
				{
					self::$isHuman = false;
				}

				$req[$name] = @$req[$alias];
				unset($req[$alias]);
			}
		}

		if (
			!self::$isHuman ||
			!$meta->isValid() ||
			time() - $meta->timestamp < $meta->delay ||
			time() - $meta->timestamp > $meta->lifetime * 60 ||
			(
				$meta->referer &&
				(!isset($_SERVER['HTTP_REFERER']) || $meta->referer != $_SERVER['HTTP_REFERER'])
			)
		)
		{
			self::$isHuman = false;
			return;
		}
		self::$isHuman = true;
	}
	//-----------------------------------------------------------------------------

	/**
	 * Возвращает true если форму отправил человек
	 *
	 * @return bool
	 */
	public static function isHuman()
	{
		if (!self::$isHandled)
		{
			self::handleRequest();
		}
		return self::$isHuman;
	}
	//-----------------------------------------------------------------------------
}