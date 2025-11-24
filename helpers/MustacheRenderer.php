<?php

class MustacheRenderer{
    private $mustache;
    private $viewsFolder;

    public function __construct($partialsPathLoader){
        Mustache_Autoloader::register();
        $this->mustache = new Mustache_Engine(
            array(
                'partials_loader' => new Mustache_Loader_FilesystemLoader( $partialsPathLoader )
            ));
        $this->viewsFolder = $partialsPathLoader;
    }

    public function render($contentFile , $data = [] ){
        $fullPath = $this->viewsFolder . '/' . $contentFile . "Vista.mustache";
        echo $this->generateHtml($fullPath, $data, true);
    }

    public function generateHtml($contentFile, $data = [], $includeLayout = true) {
        $contentAsString = "";
        if ($includeLayout) {
            $contentAsString .= file_get_contents($this->viewsFolder . '/partials/header.mustache');
        }

        $template = file_get_contents($contentFile);

        $contentAsString .= $this->mustache->render($template, $data);

        if ($includeLayout) {
            $contentAsString .= file_get_contents($this->viewsFolder . '/partials/footer.mustache');
        }

        return $contentAsString;
    }
}