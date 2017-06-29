<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit5dce060b2f834c4e24669f170dea1b4a
{
    public static $files = array (
        'dedd6b14be06bffe0377314d9ba5034d' => __DIR__ . '/../..' . '/src/includes/functions.php',
    );

    public static $prefixLengthsPsr4 = array (
        'E' => 
        array (
            'Egulias\\EmailValidator\\' => 23,
        ),
        'D' => 
        array (
            'Dwnload\\WpEmailDownload\\' => 24,
            'DrewM\\MailChimp\\' => 16,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Egulias\\EmailValidator\\' => 
        array (
            0 => __DIR__ . '/..' . '/egulias/email-validator/EmailValidator',
        ),
        'Dwnload\\WpEmailDownload\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
        'DrewM\\MailChimp\\' => 
        array (
            0 => __DIR__ . '/..' . '/drewm/mailchimp-api/src',
        ),
    );

    public static $prefixesPsr0 = array (
        'D' => 
        array (
            'Doctrine\\Common\\Lexer\\' => 
            array (
                0 => __DIR__ . '/..' . '/doctrine/lexer/lib',
            ),
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit5dce060b2f834c4e24669f170dea1b4a::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit5dce060b2f834c4e24669f170dea1b4a::$prefixDirsPsr4;
            $loader->prefixesPsr0 = ComposerStaticInit5dce060b2f834c4e24669f170dea1b4a::$prefixesPsr0;

        }, null, ClassLoader::class);
    }
}
