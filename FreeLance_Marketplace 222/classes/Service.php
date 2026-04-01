<?php
declare(strict_types=1);

class Service {
    private string $service_id;
    private string $service_title;
    private float $price;
    private int $delivery_time;
    private int $revisions_included;
    private string $image_1;

    public function __construct(
        string $service_id,
        string $service_title,
        float $price,
        int $delivery_time,
        int $revisions_included,
        string $image_1
    ) {
        $this->service_id = $service_id;
        $this->service_title = $service_title;
        $this->price = $price;
        $this->delivery_time = $delivery_time;
        $this->revisions_included = $revisions_included;
        $this->image_1 = $image_1;
    }

    public function getId(): string { return $this->service_id; }
    public function getTitle(): string { return $this->service_title; }
    public function getPrice(): float { return $this->price; }
    public function getDeliveryTime(): int { return $this->delivery_time; }
    public function getRevisionsIncluded(): int { return $this->revisions_included; }
    public function getImage(): string { return $this->image_1; }
}
