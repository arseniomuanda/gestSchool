<?php

namespace App\Services\Notifications\Channels;

interface Channel
{
    /**
     * Envia o conteúdo renderizado para o destinatário.
     *
     * @return array{address:string, error:?string} info para o log
     * @throws \Throwable em caso de falha de envio
     */
    public function send(string $recipientAddress, string $subject, string $body): array;

    /**
     * Devolve a chave esperada para o endereço (ex: email do user, telefone).
     */
    public function addressFromUser(\App\Models\User $user): ?string;
}
