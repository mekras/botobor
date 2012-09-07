<?php
/**
 * Ботобор
 *
 * Библиотека для защиты веб-форм от спама
 *
 * Основано на {@link http://nedbatchelder.com/text/stopbots.html}
 *
 * ------------------------------------------------------------------------
 * ЛИЦЕНЗИЯ
 *
 * Copyright 2012 DvaSlona Ltd.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * ------------------------------------------------------------------------
 *
 * @version 0.4.0
 *
 * @copyright 2008-2012, Михаил Красильников, <mihalych@vsepofigu.ru>
 * @license http://www.apache.org/licenses/LICENSE-2.0.html  Apache License, Version 2.0
 * @author Михаил Красильников, <mihalych@vsepofigu.ru>
 *
 * @package Botobor
 */


/**
 * Вспомогательный класс
 *
 * {@link Botobor_Form Описание и примеры.}
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
     * Настройки по умолчанию
     * @var array
     * @since 0.4.0
     */
    private static $conf = array(
        // Секретная строка для подписывания мета-данных
        'secret' => '',
        // Проверка REFERER
        'check.referer' => true,
        // Проверка задержки
        'check.delay' => true,
        // Наименьшая задержка в секундах допустимая между созданием и получением формы
        'delay' => 5,
        // Проверять время жизни
        'check.lifetime' => true,
        // Наибольшая задержка в минутах допустимая между созданием и получением формы
        'lifetime' => 30,
        // Использовать приманки
        'check.honeypots' => true,
        // Имена для приманок
        'honeypots' => array(
            'name', 'mail', 'email'
        ),
    );

    /**
     * Возвращает значение параметра настроек по умолчанию
     *
     * Возможные параметры:
     *
     * - secret (string) - секретная строка для подписывания мета-данных
     * - check.referer (bool) — проверять заголовок REFERER
     * - check.delay (bool) — проверять минимальное время между показом и отправкой формы
     * - delay (int) — наименьшая задержка в секундах допустимая между созданием и получением формы
     * - check.lifetime (bool) — проверять максимальное время между показом и отправкой формы
     * - lifetime (int) — время жизни формы в минутах (от момента создания)
     * - check.honeypots (bool) — использовать поля-приманками
     * - honeypots (string[]) — имена для приманок
     *
     * @param string $option  имя опции
     *
     * @return mixed
     *
     * @see set()
     * @since 0.4.0
     */
    public static function get($option)
    {
        assert('is_string($option)');

        if ('secret' == $option && '' === self::$conf['secret'])
        {
            self::$conf['secret'] = strval(filemtime(__FILE__));
        }
        return isset(self::$conf[$option]) ? self::$conf[$option] : null;
    }

    /**
     * Устанавливает значение параметра настроек по умолчанию
     *
     * @param string $option  имя опции
     * @param mixed  $value   новое значение
     *
     * @throws InvalidArgumentException
     *
     * @return void
     *
     * @see get()
     * @since 0.4.0
     */
    public static function set($option, $value)
    {
        if (!array_key_exists($option, self::$conf))
        {
            throw new InvalidArgumentException(sprintf('Unknown option "%s"', $option));
        }

        $expected = gettype(self::$conf[$option]);
        $actual = gettype($value);
        if ($expected != $actual)
        {
            throw new InvalidArgumentException(
                sprintf('Option "%s" has type "%s", but "%s" given',
                    $option, $expected, $actual)
            );
        }

        self::$conf[$option] = $value;
    }
}


/**
 * Мета-данные формы
 *
 * @property array  $aliases    псевдонимы полей
 * @property array  $checks     список проверок
 * @property int    $delay      задержка между созданием и отправкой
 * @property int    $lifetime   время жизни формы
 * @property string $referer    URL формы
 * @property int    $timestamp  время создания формы
 * @property string $uid        уникальный идентификатор
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
     * Создаёт новый контейнер мета-данных
     *
     * Аргумент $encodedData, если передан, должен содержать закодированные, подписанные мета-данные
     * в виде строки, которые надо импортировать.
     *
     * @param string $encodedData  закодированные подписанные данные
     *
     * @since 0.3.0
     */
    public function __construct($encodedData = null)
    {
        if ($encodedData)
        {
            $this->import($encodedData);
        }
        else
        {
            // Задаём уникальный id формы, для проверки на повторную отправку
            $this->uid = uniqid();
        }
    }

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

    /**
     * Возвращает закодированные и подписанные мета-данные
     *
     * @return string  закодированные данные
     *
     * @since 0.3.0
     */
    public function getEncoded()
    {
        $data = serialize($this->data);
        if (function_exists('gzdeflate'))
        {
            $data = gzdeflate($data);
        }
        $data = base64_encode($data);
        $data .= $this->signature($data);
        return $data;
    }

    /**
     * Декодирует и импортирует мета-данные
     *
     * @param string $encoded  закодированные данные
     *
     * @return void
     *
     * @since 0.3.0
     */
    public function import($encoded)
    {
        $signature = substr($encoded, -32);
        $data = substr($encoded, 0, -32);
        $validSignature = $this->signature($data);
        $this->isValid = $signature == $validSignature;

        if ($data)
        {
            $data = base64_decode($data);
            if (function_exists('gzinflate'))
            {
                $data = gzinflate($data);
            }
            $data = unserialize($data);
        }

        $this->data = $data ? $data : array();
    }

    /**
     * Возвращает true если данные достоверны
     *
     * @return bool
     *
     * @since 0.1.0
     * @see import()
     */
    public function isValid()
    {
        return $this->isValid;
    }

    /**
     * Возвращает подпись для указанных данных
     *
     * @param string $data  подписываемые данные
     *
     * @return string  подпись
     *
     * @since 0.3.0
     */
    protected function signature($data)
    {
        $signature = md5($data . Botobor::get('secret'));
        return $signature;
    }
}



/**
 * Обёртка для защиты веб-формы
 *
 * Основано на {@link http://nedbatchelder.com/text/stopbots.html}
 *
 * Считается, что форма заполнена роботом, если:
 * - либо между созданием формы и её отправкой прошло слишком мало времени
 *   (см. опцию {@link Botobor_Form::__construct() delay});
 * - либо между созданием формы и её отправкой прошло слишком много времени
 *   (см. опцию {@link Botobor_Form::__construct() lifetime});
 * - либо заполнено хотя бы одно поле-приманка (см. {@link Botobor_Form::setHoneypot()});
 * - либо заголовок REFERER не совпадает с адресом, где была размещена форма.
 *
 * Любая из проверок может быть отключена.
 *
 * <b>ИСПОЛЬЗОВАНИЕ</b>
 *
 * Файл, создающий форму:
 * <code>
 * require 'botobor.php';
 * …
 * // Получите разметку формы тем способом, который предусмотрен у вас в проекте, например:
 * $html = $form->getHTML();
 * // Создайте объект-обёртку:
 * $bform = new Botobor_Form($html);
 * // Получите новую разметку формы
 * $html = $bform->getCode();
 * </code>
 *
 * Файл, обрабатывающий форму:
 * <code>
 * require 'botobor.php';
 * …
 * if (Botobor_Keeper::get()->isRobot())
 * {
 *     // Форма отправлена роботом, выводим сообщение об ошибке.
 * }
 * </code>
 *
 * Подробнее в {@link http://mekras.github.com/botobor/ документации}.
 *
 * @package Botobor
 */
class Botobor_Form
{
    /**
     * Защищаемая форма
     *
     * @var string
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
     * Аргумент $form должен содержать разметку формы.
     *
     * @param string $form  разметка формы
     *
     * @return Botobor_Form
     */
    public function __construct($form)
    {
        $this->form = $form;

        $this->meta = new Botobor_MetaData();
        $this->meta->checks = array(
            'referer' => Botobor::get('check.referer'),
            'delay' => Botobor::get('check.delay'),
            'lifetime' => Botobor::get('check.lifetime'),
            'honeypots' => Botobor::get('check.honeypots'),
        );

        $this->setDelay(Botobor::get('delay'));
        $this->setLifetime(Botobor::get('lifetime'));
        $this->honeypots = Botobor::get('honeypots');

        $this->meta->timestamp = time();

        if (isset($_SERVER['REQUEST_URI']) && $_SERVER['HTTP_HOST'])
        {
            $this->meta->referer = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ? 'https' : 'http';
            $this->meta->referer .= '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        }
    }

    /**
     * Включает или отключает проверку
     *
     * @param string $check  имя проверки
     * @param bool   $state  новое состояние
     *
     * @return void
     *
     * @since 0.2.0
     * @see Botobor::set() для более подробной информации
     */
    public function setCheck($check, $state)
    {
        if (array_key_exists($check, $this->meta->checks))
        {
            $this->meta->checks[$check] = $state;
        }
    }

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

    /**
     * Добавляет имя поля в список имён полей-приманки
     *
     * @param string $name  имя поля ввода, которое надо заменить приманкой
     *
     * @return void
     *
     * @since 0.3.0
     */
    public function addHoneypot($name)
    {
        $this->honeypots []= $name;
    }

    /**
     * Возвращает разметку защищённой формы
     *
     * @return string  HTML
     *
     * @since 0.3.0
     */
    public function getCode()
    {
        $html = $this->form;

        if ($this->meta->checks['honeypots'])
        {
            $html = $this->createHoneypots($html, $this->honeypots);
        }

        $botoborData =
            '<div style="display: none;">' .
            $this->createInput('hidden', Botobor::META_FIELD_NAME, $this->meta->getEncoded()) .
            '</div>';

        $html = preg_replace('/(<form[^>]*>)/si', '$1'.$botoborData, $html);

        return $html;
    }

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
     * @since 0.3.0
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

    /**
     * Заменяет поля ввода полями-приманками
     *
     * @param string $html   HTML-разметка формы
     * @param array  $names  Массив имён полей
     *
     * @return string  HTML
     *
     * @since 0.3.0
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
}



/**
 * Класс проверки принятой формы
 *
 * Проверяет форму, принятую в обрабатываемом запросе. При этом проводится обратная замена
 * полей-приманок на исходные поля формы.
 *
 * @package Botobor
 * @link botobor.php Описание и примеры
 * @see isHuman()
 */
class Botobor_Keeper
{
    /**
     * Экземпляр-одиночка
     * @var self
     * @since 0.4.0
     */
    private static $instance = null;

    /**
     * Признак того, что запрос уже обработан
     *
     * @var bool
     * @since 0.1.0
     */
    private $isHandled = false;

    /**
     * Признак того, что форму заполнил робот
     *
     * @var bool
     * @since 0.1.0
     */
    private $isRobot = false;

    /**
     * Признак повторной отправки формы
     *
     * @var bool
     * @since 0.3.1
     */
    private $isResubmit = false;

    /**
     * Провалившаяся проверка
     *
     * @var null|string
     * @since 0.4.0
     */
    private $failedCheck = null;

    /**
     * Обрабатывает текущий запрос
     *
     * Если в текущем запросе содержатся мета-данные Ботобора, они извлекаются и проверяются.
     * Проводится обратная замена полей-приманок на исходные поля формы.
     *
     * Этот метод вызывается автоматически из {@link isRobot()}. Но разработчики могут вызывать его
     * самостоятельно, если их приложение получает аргументы запроса не напрямую из $_GET или $_POST,
     * а через посредничество другого класса, который извлекает аргументы до обращения к
     * {@link isRobot()}. В этом случае надо вызвать {@link handleRequest()} до создания
     * класса-посредника.
     *
     * Другой способ, если значения извлечённые из $_GET/$_POST хранятся классом-посредником в
     * массиве — передать этот массив в качестве аргумента $req при вызове метода. В этом случае
     * будет обработан этот массив, а не $_GET/$_POST.
     *
     * @param array &$req  аргументы запроса
     *
     * @return void
     *
     * @since 0.1.0
     */
    public function handleRequest(array &$req = null)
    {
        if (!isset($_SESSION['botobor']))
        {
            $_SESSION['botobor'] = array('handled' => array());
        }

        $this->isHandled = true;
        $this->isRobot = false;
        $this->failedCheck = null;

        /*
         * Если аргументы не переданы, получаем их самостоятельно
         */
        if (is_null($req))
        {
            switch (strtoupper(@$_SERVER['REQUEST_METHOD']))
            {
                case 'GET':
                    $req =& $_GET;
                    break;

                case 'POST':
                    $req =& $_POST;
                    break;

                default:
                    $this->isRobot = true;
                    $this->failedCheck = 'error';
                    return;
            }
        }

        /* Проверяем наличие мета-данных */
        if (!isset($req[Botobor::META_FIELD_NAME]))
        {
            $this->failedCheck = 'meta';
            $this->isRobot = true;
            return;
        }

        // Получаем мета-данные
        $meta = new Botobor_MetaData($req[Botobor::META_FIELD_NAME]);

        $this->isRobot =
            !$meta->isValid() ||
            // эта проверка обязательно должна быть первой. см. ниже
            !$this->testHoneypots($meta, $req) ||
            !$this->testReferer($meta) ||
            !$this->testTimings($meta);

        if (!$meta->isValid())
        {
            $this->failedCheck = 'meta';
        }

        $this->isResubmit = in_array($meta->uid, $_SESSION['botobor']['handled']);

        $_SESSION['botobor']['handled'] []= $meta->uid;
    }

    /**
     * Возвращает true если форму отправил робот
     *
     * @return bool
     *
     * @since 0.4.0
     */
    public function isRobot()
    {
        if (!$this->isHandled)
        {
            $this->handleRequest();
        }
        return $this->isRobot;
    }

    /**
     * Возвращает true если форма была отправлена повторно
     *
     * @return bool
     *
     * @since 0.3.1
     */
    public function isResubmit()
    {
        if (!$this->isHandled)
        {
            $this->handleRequest();
        }
        return $this->isResubmit;
    }

    /**
     * Возвращает имя провалившейся проверки или null
     *
     * Имена проверок такие же как используются в методе {@link Botobor::set()}, но без префикса
     * «check.»: delay, lifetime и т. д.
     *
     * Кроме них могут также возвращаться другие значения:
     *
     * - meta — мета-данные отсутствуют или искажены
     * - error — произошла ошибка при анализе запроса
     *
     * @return null|string
     * @since 0.4.0
     */
    public function getFailedCheck()
    {
        return $this->failedCheck;
    }

    /**
     * Возвращает экземпляр-одиночку класса {@link Botobor_Keeper}
     *
     * @return Botobor_Keeper
     *
     * @since 0.4.0
     */
    public static function get()
    {
        if (null === self::$instance)
        {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Запрещает создание объектов этого класса другими классами
     */
    //@codeCoverageIgnoreStart
    private function __construct()
    {
    }
    //@codeCoverageIgnoreEnd

    /**
     * Запрещает клонирование объекта
     */
    //@codeCoverageIgnoreStart
    private function __clone()
    {
    }
    //@codeCoverageIgnoreEnd

    /**
     * Проверяет поля-приманки
     *
     * @param Botobor_MetaData $meta
     * @param array            $req
     *
     * @return bool
     *
     * @since 0.3.0
     */
    private function testHoneypots(Botobor_MetaData $meta, array &$req)
    {
        $result = true;
        if ($meta->aliases)
        {
            foreach ($meta->aliases as $alias => $name)
            {
                if (isset($req[$name]) && $req[$name])
                {
                    $this->failedCheck = 'honeypots';
                    $result = false;
                    /*
                     * Не прерываем выполнение метода, чтобы восстановить правильные имена для всех
                     * параметров
                     */
                }

                $req[$name] = @$req[$alias];
                unset($req[$alias]);
            }
        }
        return $result;
    }

    /**
     * Проверяем ссылающийся адрес
     *
     * @param Botobor_MetaData $meta
     *
     * @return bool
     *
     * @since 0.3.0
     */
    private function testReferer(Botobor_MetaData $meta)
    {
        if (@!$meta->checks['referer'] ||
            !$meta->referer ||
            (isset($_SERVER['HTTP_REFERER']) && $meta->referer == $_SERVER['HTTP_REFERER']))
        {
            return true;
        }
        else
        {
            $this->failedCheck = 'referer';
            return false;
        }
    }

    /**
     * Проверяет задержку и время жизни
     *
     * @param Botobor_MetaData $meta
     *
     * @return bool
     *
     * @since 0.3.0
     */
    private function testTimings(Botobor_MetaData $meta)
    {
        /* Проверяем задержку */
        if (
            $meta->checks['delay'] &&
            (time() - $meta->timestamp < $meta->delay)
        )
        {
            $this->failedCheck = 'delay';
            return false;
        }

        /* Проверяем время жизни */
        if (
            $meta->checks['lifetime'] &&
            (time() - $meta->timestamp > $meta->lifetime * 60)
        )
        {
            $this->failedCheck = 'lifetime';
            return false;
        }

        return true;
    }
}
