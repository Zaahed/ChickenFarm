<?php
declare(strict_types=1);


class Barn
{
    /**
     * @var Chicken[]
     */
    private array $chickens = [];
    private DateTime $currentDate;
    private int $totalNewBornChickens = 0;
    private int $producedEggs = 0;
    private int $fertilizedEggs = 0;
    private ?Chicken $mostProducedEggsChicken = null;
    private ?Chicken $mostFertilizedEggsChicken = null;

    /**
     * @param int $numberOfChickens
     */
    public function __construct(int $numberOfChickens)
    {
        $this->currentDate = new DateTime('2023-01-01');
        $this->addChickens($numberOfChickens);
    }

    /**
     * Add a number of chickens to the barn.
     *
     * @param int $numberOfChickens
     * @return $this
     */
    private function addChickens(int $numberOfChickens): self
    {
        if ($numberOfChickens < 0) {
            throw new \InvalidArgumentException(
                'Number of chickens needs to be zero or larger.'
            );
        }

        for ($i = 0; $i < $numberOfChickens; $i++) {
            $this->chickens[] = new Chicken($this->currentDate);
        }

        return $this;
    }

    /**
     * Simulate a certain amount of chicken days.
     *
     * @param int $days
     * @return $this
     */
    public function simulate(int $days): self
    {
        for ($i = 0; $i < $days; $i++) {
            if ($i === 0) {
                $this->resetStats();
            }
            foreach ($this->chickens as $chicken) {
                $chicken->simulateDay();
                $this->chickens = array_merge(
                    $this->chickens,
                    $chicken->getNewChickens()
                );
                $this->totalNewBornChickens += count($chicken->getNewChickens());

                if ($i === $days - 1) {
                    $this->collectStats($chicken);
                }
            }

            $this->currentDate->modify('+1 day');
        }

        return $this;
    }

    /**
     * Set stats back to zero.
     *
     * @return void
     */
    private function resetStats(): void
    {
        $this->producedEggs = 0;
        $this->fertilizedEggs = 0;
        $this->mostProducedEggsChicken = null;
        $this->mostFertilizedEggsChicken = null;
    }

    /**
     * Add chicken stats to totals.
     *
     * @param Chicken $chicken
     * @return void
     */
    private function collectStats(Chicken $chicken): void
    {
        $this->producedEggs += $chicken->getProducedEggs();
        $this->fertilizedEggs += $chicken->getFertilizedEggs();

        if ($this->mostProducedEggsChicken === null ||
            $chicken->getProducedEggs() > $this->mostProducedEggsChicken->getProducedEggs()) {
            $this->mostProducedEggsChicken = $chicken;
        }

        if ($this->mostFertilizedEggsChicken === null ||
            $chicken->getFertilizedEggs() > $this->mostFertilizedEggsChicken->getFertilizedEggs()) {
            $this->mostFertilizedEggsChicken = $chicken;
        }

    }

    /**
     * Get total produced eggs for simulated period.
     *
     * @return int
     */
    public function getTotalProducedEggs(): int
    {
        return $this->producedEggs;
    }

    /**
     * Get total fertilized eggs for simulated period.
     *
     * @return int
     */
    public function getTotalFertilizedEggs(): int
    {
        return $this->fertilizedEggs;
    }

    /**
     * Get chicken with the most produced eggs.
     *
     * @return Chicken|null
     */
    public function getMostProducedEggsChicken(): ?Chicken
    {
        return $this->mostProducedEggsChicken;
    }

    /**
     * Get chicken with the most fertilized eggs.
     *
     * @return Chicken|null
     */
    public function getMostFertilizedEggsChicken(): ?Chicken
    {
        return $this->mostFertilizedEggsChicken;
    }

    /**
     * Get total revenue formatted in euros for unfertilized eggs.
     *
     * @return string
     */
    public function getTotalRevenue(): string
    {
        $unfertilizedEggs = $this->getTotalProducedEggs() - $this->getTotalFertilizedEggs();
        $revenue = $unfertilizedEggs * 0.25;

        $numberFormatter = new NumberFormatter("nl", NumberFormatter::CURRENCY);
        return $numberFormatter->formatCurrency($revenue, "EUR");

    }

    /**
     * Get the total amount of newborn chickens.
     *
     * @return int
     */
    public function getTotalNewBornChickens(): int
    {
        return $this->totalNewBornChickens;
    }
}

class Chicken
{
    private const DEFAULT_BIRTH_MONTH = 4;
    private const DEFAULT_BIRTH_YEAR = 2022;
    private DateTime $currentDate;
    private string $id;
    private DateTime $dob;
    private int $eggsProduced = 0;
    private int $eggsFertilized = 0;

    /**
     * @var Chicken[]
     */
    private array $newChickens = [];

    /**
     * @param DateTime $currentDate
     * @param DateTime|null $dob
     */
    public function __construct(DateTime $currentDate, ?DateTime $dob = null)
    {
        if ($dob === null) {
            $this->initDob();
        } else {
            $this->dob = $dob;
        }
        $this->initId();
        $this->currentDate = $currentDate;
    }

    /**
     * Get chicken ID.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Set chicken ID.
     *
     * @return void
     */
    private function initId(): void
    {
        $this->id = $this->dob->format('Ymd-') . mt_rand(0, 9999);
    }

    /**
     * Generate random date of birth for chicken.
     *
     * @return void
     */
    private function initDob(): void
    {
        $daysInMonth = cal_days_in_month(
            CAL_GREGORIAN,
            self::DEFAULT_BIRTH_MONTH,
            self::DEFAULT_BIRTH_YEAR
        );
        $day = mt_rand(1, $daysInMonth);

        $date = new \DateTime();
        $date->setDate(
            self::DEFAULT_BIRTH_YEAR,
            self::DEFAULT_BIRTH_MONTH,
            $day
        );

        $this->dob = $date;
    }

    /**
     * Get age of the chicken in months.
     *
     * @return int
     */
    private function getAgeInMonths(): int
    {
        $dateInterval = $this->dob->diff($this->currentDate);
        return $dateInterval->y * 12 + $dateInterval->m;
    }

    /**
     * Simulate a chicken day.
     *
     * @return void
     */
    public function simulateDay(): void
    {
        if ($this->getAgeInMonths() < 4) {
            return;
        }

        $this->newChickens = [];
        $producedEggs = mt_rand(0, 2);
        $this->eggsProduced += $producedEggs;

        if ($this->getAgeInMonths() < 8) {
            return;
        }

        for ($i = 0; $i < $producedEggs; $i++) {
            if (mt_rand(0, 99) > 50) {
                $this->eggsFertilized++;
                $this->newChickens[] = new Chicken(
                    $this->currentDate,
                    $this->currentDate
                );
            }
        }
    }

    /**
     * Get produced eggs.
     *
     * @return int
     */
    public function getProducedEggs(): int
    {
        return $this->eggsProduced;
    }

    /**
     * Get fertilized eggs.
     *
     * @return int
     */
    public function getFertilizedEggs(): int
    {
        return $this->eggsFertilized;
    }

    /**
     * Get newly hatched chickens.
     *
     * @return Chicken[]
     */
    public function getNewChickens(): array
    {
        return $this->newChickens;
    }

}

$barn = new Barn(50);
$barn->simulate(365);

echo "How many eggs are produced in the measured period ? \n";
echo sprintf("Eggs produced: %s \n\n", $barn->getTotalProducedEggs());

echo "How many eggs will be fertilized in the measured period ( The chance for a fertilized
egg will be 50% ) ? \n";
echo sprintf("Eggs fertilized: %s \n\n", $barn->getTotalFertilizedEggs());

echo "Which of the chickens produced the most eggs in the measured period ? \n";
echo sprintf(
    "Chicken with ID %s produced the most eggs with a number of %s eggs. \n\n",
    $barn->getMostProducedEggsChicken()->getId(),
    $barn->getMostProducedEggsChicken()->getProducedEggs()
);

echo "Which of the chickens fertilized the most eggs in the measured period ? \n";
echo sprintf(
    "Chicken with ID %s fertilized the most eggs with a number of %s eggs. \n\n",
    $barn->getMostFertilizedEggsChicken()->getId(),
    $barn->getMostFertilizedEggsChicken()->getProducedEggs()
);

echo "What will be the total revenue when the unfertilized eggs will be sold for 0.25 cent
each ? \n";
echo sprintf("Total revenue: %s euro \n\n", $barn->getTotalRevenue());

echo "How many new chickens will be born in the measured period ( a new born chicken
can lay eggs 4 months after his egg was produced and can have fertilized eggs 8
months after his egg was produced ) ? \n";
echo sprintf("Newborn chickens: %s \n\n", $barn->getTotalNewBornChickens());