<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Support\Facades\DB;

class PlayerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data
        Player::query()->delete();

        // Get all teams
        $teams = Team::all();

        // Realistic player names by position
        $goalkeepers = [
            'Manuel Neuer', 'Alisson Becker', 'Jan Oblak', 'Edouard Mendy',
            'Gianluigi Donnarumma', 'Thibaut Courtois', 'Keylor Navas', 'Mike Maignan',
            'Wojciech Szczesny', 'Kasper Schmeichel', 'Bernd Leno', 'David de Gea',
            'Hugo Lloris', 'Ederson', 'Rui Patricio', 'Samir Handanovic',
            'Marc-Andre ter Stegen', 'Unai Simon', 'Kevin Trapp', 'Walter Benitez'
        ];

        $defenders = [
            'Virgil van Dijk', 'Sergio Ramos', 'Gerard Pique', 'Kalidou Koulibaly',
            'Marquinhos', 'Matthijs de Ligt', 'Leonardo Bonucci', 'Raphael Varane',
            'Nicolas Otamendi', 'Aymeric Laporte', 'Harry Maguire', 'John Stones',
            'Tyrone Mings', 'Joe Gomez', 'Joel Matip', 'Ibrahima Konate',
            'Antonio Rudiger', 'Cesar Azpilicueta', 'Benjamin Mendy', 'Luke Shaw',
            'Andrew Robertson', 'Trent Alexander-Arnold', 'Kyle Walker', 'Kieran Trippier',
            'Reece James', 'Ben Chilwell', 'Joao Cancelo', 'Nathan Aké',
            'Ruben Dias', 'Aymeric Laporte', 'Stefan de Vrij', 'Stefan de Vrij'
        ];

        $midfielders = [
            'Kevin De Bruyne', 'Luka Modric', 'N\Golo Kante', 'Casemiro',
            'Joshua Kimmich', 'Frenkie de Jong', 'Paul Pogba', 'Marco Verratti',
            'Jorginho', 'Thiago Alcantara', 'Ilkay Gundogan', 'Bernardo Silva',
            'Bruno Fernandes', 'Mason Mount', 'Phil Foden', 'Jack Grealish',
            'Raheem Sterling', 'Jadon Sancho', 'Marcus Rashford', 'Bukayo Saka',
            'Pedro Goncalves', 'Bruno Fernandes', 'Joao Felix', 'Bernardo Silva',
            'Rafael Leao', 'Andre Silva', 'Diogo Dalot', 'Nelson Semedo',
            'Joao Moutinho', 'William Carvalho', 'Ruben Neves', 'Sébastien Haller'
        ];

        $forwards = [
            'Lionel Messi', 'Cristiano Ronaldo', 'Robert Lewandowski', 'Kylian Mbappe',
            'Erling Haaland', 'Neymar Jr', 'Karim Benzema', 'Harry Kane',
            'Romelu Lukaku', 'Sadio Mane', 'Mohamed Salah', 'Ciro Immobile',
            'Lautaro Martinez', 'Dusan Vlahovic', 'Andrej Kramaric', 'Wissam Ben Yedder',
            'Patrik Schick', 'Christopher Nkunku', 'Jonathan David', 'Luka Jovic',
            'Alvaro Morata', 'Joao Felix', 'Antoine Griezmann', 'Ousmane Dembele',
            'Kingsley Coman', 'Leroy Sane', 'Serge Gnabry', 'Thomas Muller',
            'Jamal Musiala', 'Florian Wirtz', 'Kai Havertz', 'Timo Werner'
        ];

        foreach ($teams as $team) {
            $players = [];
            $usedJerseyNumbers = [];

            // Add 2 Goalkeepers (jersey 1, 12)
            $goalkeeperJerseys = [1, 12];
            foreach ($goalkeeperJerseys as $index => $jersey) {
                $players[] = [
                    'team_id' => $team->id,
                    'full_name' => $goalkeepers[($team->id * 2 + $index) % count($goalkeepers)],
                    'position' => 'Goalkeeper',
                    'jersey_number' => $jersey,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
                $usedJerseyNumbers[] = $jersey;
            }

            // Add 5 Defenders (jersey 2-6)
            for ($i = 0; $i < 5; $i++) {
                $jersey = $i + 2;
                $players[] = [
                    'team_id' => $team->id,
                    'full_name' => $defenders[($team->id * 5 + $i) % count($defenders)],
                    'position' => 'Defender',
                    'jersey_number' => $jersey,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
                $usedJerseyNumbers[] = $jersey;
            }

            // Add 6 Midfielders (jersey 7-11, 13-15, but skip 12 as it's goalkeeper)
            $midfielderJerseys = [7, 8, 9, 10, 11, 13];
            foreach ($midfielderJerseys as $index => $jersey) {
                $players[] = [
                    'team_id' => $team->id,
                    'full_name' => $midfielders[($team->id * 6 + $index) % count($midfielders)],
                    'position' => 'Midfielder',
                    'jersey_number' => $jersey,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
                $usedJerseyNumbers[] = $jersey;
            }

            // Add 3 Forwards (jersey 14-16, but avoid conflicts)
            $forwardJerseys = [14, 15, 16];
            foreach ($forwardJerseys as $index => $jersey) {
                // Skip if jersey number is already used
                while (in_array($jersey, $usedJerseyNumbers)) {
                    $jersey++;
                }
                
                $players[] = [
                    'team_id' => $team->id,
                    'full_name' => $forwards[($team->id * 3 + $index) % count($forwards)],
                    'position' => 'Forward',
                    'jersey_number' => $jersey,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
                $usedJerseyNumbers[] = $jersey;
            }

            // Insert all players for this team
            Player::insert($players);
        }

        $this->command->info('Player seeder completed successfully!');
        $this->command->info('Created 16 players per team (256 total players) across all teams.');
    }
}
