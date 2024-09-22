<?php

namespace FMLS\TPago;

class TPagoConfig {
    private string $publicKey;
    private string $privateKey;
    private bool $isSandbox;
    private string $baseUrl;
    private string $commerceCode;
    private string $branchCode;

    
    public function __construct(string $publicKey, string $privateKey, string $commerceCode, string $branchCode, bool $isSandbox = true)
    {
        $this->publicKey = $publicKey;
        $this->privateKey = $privateKey;
        $this->isSandbox = $isSandbox;
        $this->commerceCode = $commerceCode;
        $this->branchCode = $branchCode;

        // Establecer la URL base según el entorno.
        $this->baseUrl = $this->isSandbox ? 'https://vpos.infonet.com.py:8888' : 'https://vpos.infonet.com.py';
    }

    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    public function getPrivateKey(): string
    {
        return $this->privateKey;
    }

    public function getCommerceCode(): string
    {
        return $this->commerceCode;
    }

    public function getBranchCode(): string
    {
        return $this->branchCode;
    }

    public function getBaseUrl(): string
    {
        $url_path = "/external-commerce/api/0.1/commerces/$this->commerceCode/branches/$this->branchCode";
        return $this->baseUrl . $url_path;
    }
}