@extends('errors._shell', [
    'code' => 403,
    'label' => 'Record sealed',
    'headline' => 'Access denied.',
    'message' => 'This record exists, but it is sealed. You do not have permission to view it.',
    'hint' => 'If this is your project, sign in to the creator studio to manage it.',
])
