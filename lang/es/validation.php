<?php

/*
 * Mensajes de validación de Laravel y Livewire en español paraguayo. Sin este archivo, un error de subida de archivos se
 * muestra como un código interno («validation.uploaded»). Las pantallas con fotos igual traen su propio mensaje más claro.
 */
return [
    'uploaded' => 'No se pudo subir el archivo. Puede ser muy pesado o la señal falló: probá de nuevo.',
    'file' => 'El campo :attribute tiene que ser un archivo.',
    'image' => 'El campo :attribute tiene que ser una imagen.',
    'mimes' => 'El campo :attribute tiene que ser un archivo de tipo: :values.',
    'mimetypes' => 'El campo :attribute tiene que ser un archivo de tipo: :values.',
    'required' => 'El campo :attribute es obligatorio.',
    'string' => 'El campo :attribute tiene que ser un texto.',
    'integer' => 'El campo :attribute tiene que ser un número entero.',
    'numeric' => 'El campo :attribute tiene que ser un número.',
    'max' => [
        'array' => 'El campo :attribute no puede tener más de :max elementos.',
        'file' => 'El archivo :attribute no puede pesar más de :max kilobytes.',
        'numeric' => 'El campo :attribute no puede ser mayor que :max.',
        'string' => 'El campo :attribute no puede tener más de :max caracteres.',
    ],
    'min' => [
        'array' => 'El campo :attribute tiene que tener al menos :min elementos.',
        'file' => 'El archivo :attribute tiene que pesar al menos :min kilobytes.',
        'numeric' => 'El campo :attribute tiene que ser al menos :min.',
        'string' => 'El campo :attribute tiene que tener al menos :min caracteres.',
    ],
    'attributes' => [],
];
