<?php

require __DIR__ . '/vendor/autoload.php';

class RoboFile extends \Robo\Tasks {

    /**
     * Creates release zip
     *
     * @param string $package
     * @param string $version
     */
    public function release( $package, $version = 'dev-master'  ) {

        list( $vendor, $name ) = explode( '/', $package );

        $this->_mkdir( 'release' );

        $this->taskExec( "composer create-project {$package} {$name} {$version}" )
             ->dir( __DIR__ . '/release' )
             ->arg( '--prefer-dist' )
             ->arg( '--no-dev' )
             ->run();

        $this->taskExec( 'composer remove composer/installers --update-no-dev' )
             ->dir( __DIR__ . "/release/{$name}" )
             ->run();

        $this->taskExec( 'composer dump-autoload --optimize' )
             ->dir( __DIR__ . "/release/{$name}" )
             ->run();

        $zipFile = "release/{$name}-{$version}.zip";

        $this->_remove( $zipFile );

        $this->taskPack( $zipFile )
             ->addDir( $name, "release/{$name}" )
             ->run();

        $this->_deleteDir( "release/{$name}" );
    }
}
