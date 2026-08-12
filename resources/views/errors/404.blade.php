@extends('errors._shell', [
    'code' => 404,
    'label' => 'Record not found',
    'headline' => 'Not on file.',
    'message' => 'This record was never filed, has been withdrawn, or the URL is mistyped. Nothing matches this entry in the registry.',
    'hint' => 'Check the address, or browse every filed launch from the registry.',
])
