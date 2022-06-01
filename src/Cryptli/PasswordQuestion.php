<?php

namespace Cryptli;

use Cryptli\Exception as Ex;
use Symfony\Component\Console\Question\Question;

class PasswordQuestion extends Question
{

    /**
     * PasswordQuestion constructor.
     *
     * @param string $question
     * @param null $default
     * @param bool $hidden
     * @param bool $hiddenFallback
     * @param int $maxAttempts
     * @param callable|null $validator
     */
    public function __construct(
        string $question = 'Please enter a password',
        $default = null,
        bool $hidden = true,
        bool $hiddenFallback = false,
        int $maxAttempts = 3,
        callable $validator = null
    ) {

        parent::__construct($question, $default);

        $this->setHidden($hidden)
            ->setHiddenFallback($hiddenFallback)
            ->setMaxAttempts($maxAttempts)
            ->setValidator($validator);

        if ($validator === null) {
            $this->setValidator(function ($value) {
                if (trim($value) === '') {
                    throw new Ex\EmptyPasswordException();
                }

                return $value;
            });
        }
    }
}
