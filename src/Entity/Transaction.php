<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\TransactionRepository;

#[ORM\Entity(repositoryClass: TransactionRepository::class)]
#[ORM\Table(name: '`transaction`')]
#[ORM\HasLifecycleCallbacks()]
class Transaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]

    private ?int $id = null;

    #[ORM\Column]

    private ?int $amount = null;

    #[ORM\Column(length: 255)]

    private ?string $transaction_code = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]

    private ?\DateTimeInterface $date = null;

    #[ORM\Column(length: 50)]

    private ?string $type = null;

    #[ORM\ManyToOne(inversedBy: 'transactions')]
    private ?Customers $sender_id = null;

    #[ORM\Column(length: 255)]

    private ?string $receiver_phone = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAmount(): ?int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getTransactionCode(): ?string
    {
        return $this->transaction_code;
    }

    public function setTransactionCode(string $transaction_code): self
    {
        $this->transaction_code = $transaction_code;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getSenderId(): ?Customers
    {
        return $this->sender_id;
    }

    public function setSenderId(?Customers $sender_id): self
    {
        $this->sender_id = $sender_id;

        return $this;
    }

    public function getReceiverPhone(): ?string
    {
        return $this->receiver_phone;
    }

    public function setReceiverPhone(string $receiver_phone): self
    {
        $this->receiver_phone = $receiver_phone;

        return $this;
    }
    

    #[ORM\PrePersist]
    public function onPrePersist(){
        $this->date = new \DateTime();
        $this->transaction_code = uniqid("TR");
    }

    public function returnArray(){
        $arrayCustomer = [
            "receiver_phone" => $this->getReceiverPhone(),
            "type" => $this->getType(),
            "amount" => $this->getAmount(),
            "transaction_code" => $this->getTransactionCode(),
            "transaction_date" => $this->getDate(),
        ];

        return $arrayCustomer;
    }

}
