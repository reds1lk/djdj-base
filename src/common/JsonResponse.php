<?php

namespace Djdj\Base\common;

use think\Response;

class JsonResponse
{
    private int $code;
    private string $msg;
    private $data;
    private array $trace;
    private bool $encrypt;
    private bool $showTrace;

    public function __construct($code = 1, $msg = '成功', $data = null, $trace = [])
    {
        $this->code = $code;
        $this->msg = $msg;
        $this->data = $data;
        $this->trace = $trace;
        $this->encrypt = AesUtils::isEncrypted();
        $this->showTrace = false;
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function setCode(int $code): void
    {
        $this->code = $code;
    }

    public function getMsg(): string
    {
        return $this->msg;
    }

    public function setMsg(string $msg): void
    {
        $this->msg = $msg;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData($data): void
    {
        $this->data = $data;
    }

    public function getTrace(): array
    {
        return $this->trace;
    }

    public function setTrace(array $trace): void
    {
        $this->trace = $trace;
    }

    public function setEncrypt(bool $isEncrypt): JsonResponse
    {
        $this->encrypt = $isEncrypt;
        return $this;
    }

    public function setShowTrace(bool $isShowTrace): JsonResponse
    {
        $this->showTrace = $isShowTrace;
        return $this;
    }

    public function json(): Response
    {
        $data = [
            'code' => $this->code,
            'msg' => $this->msg,
            'data' => $this->encrypt ? AesUtils::encrypt($this->data) : $this->data,
        ];
        if ($this->showTrace) {
            $data['trace'] = $this->encrypt ? AesUtils::encrypt($this->trace) : $this->trace;
        }
        return json($data);
    }
}