<?php
class Controller {
    protected function view($path, $data = []) {
        extract($data);
        require __DIR__ . '/../app/views/' . $path;
    }

    protected function renderPartial($partial, $data = []) {
        extract($data);
        require __DIR__ . '/../app/views/partials/' . $partial;
    }
}
