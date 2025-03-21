<?php

declare(strict_types=1);

namespace Vultr\VultrPhp\Services\Billing;

use Vultr\VultrPhp\Util\Model;

/**
 * Holds billing pending charges information.
 */
class PendingCharge extends Model
{
	protected string $description;
	protected string $product;
	protected string $startDate;
	protected string $endDate;
	protected int $units;
	protected string $unitType;
	protected float $unitPrice;
	protected float $total;

	public function getDescription() : string
	{
		return $this->description;
	}

	public function setDescription(string $description) : void
	{
		$this->description = $description;
	}

	public function getProduct() : string
	{
		return $this->product;
	}

	public function setProduct(string $product) : void
	{
		$this->product = $product;
	}

	public function getStartDate() : string
	{
		return $this->startDate;
	}

	public function setStartDate(string $start_date) : void
	{
		$this->startDate = $start_date;
	}

	public function getEndDate() : string
	{
		return $this->endDate;
	}

	public function setEndDate(string $end_date) : void
	{
		$this->endDate = $end_date;
	}

	public function getUnits() : int
	{
		return $this->units;
	}

	public function setUnits(int $units) : void
	{
		$this->units = $units;
	}

	public function getUnitType() : string
	{
		return $this->unitType;
	}

	public function setUnitType(string $unit_type) : void
	{
		$this->unitType = $unit_type;
	}

	public function getUnitPrice() : float
	{
		return $this->unitPrice;
	}

	public function setUnitPrice(float $unit_price) : void
	{
		$this->unitPrice = $unit_price;
	}

	public function getTotal() : float
	{
		return $this->total;
	}

	public function setTotal(float $total) : void
	{
		$this->total = $total;
	}

	public function getResponseName() : string
	{
		return 'pending_charge';
	}

	public function getModelExceptionClass() : string
	{
		return str_replace('PendingCharge', 'Billing', parent::getModelExceptionClass());
	}
}
