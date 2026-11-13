<?php

namespace Modules\Team\Controllers;

use App\Controllers\PublicController;
use Modules\Team\Models\TeamModel;

/**
 * Our Team and Partners (design: html/our-team.htm, html/partners.htm),
 * routed through wl_meta_tags (MetaModel::route_for()):
 *
 *   /our-team   index      row entity_type team/index
 *   /partners   partners   row entity_type team/partners
 *
 * The active rows of Sitepanel > Manage Team & Partners of each type, in
 * display order; an entry whose image file is missing is shown without it.
 */
class Team extends PublicController
{
    public const IMAGE_FOLDER = 'team';

    protected TeamModel $team_model;

    protected function init(): void
    {
        $this->team_model = new TeamModel();
    }

    public function index(): string
    {
        $rows = $this->team_model->get_active(TeamModel::TYPE_TEAM);
        $rows === false && log_message('error', 'Team::index could not load the team.');

        return $this->render('Modules\Team\Views\our_team', [
            'page_meta'   => ['title' => 'Our Team | ' . $this->site_settings->get('site_name'), 'description' => 'Meet the people behind CustomCart.'],
            'members'     => $this->with_images(is_array($rows) ? $rows : [], 600, 600, 'C'),
            'unavailable' => $rows === false,
        ]);
    }

    public function partners(): string
    {
        $rows = $this->team_model->get_active(TeamModel::TYPE_PARTNER);
        $rows === false && log_message('error', 'Team::partners could not load the partners.');

        return $this->render('Modules\Team\Views\partners', [
            'page_meta'   => ['title' => 'Our Partners | ' . $this->site_settings->get('site_name'), 'description' => 'The partners who help CustomCart make and deliver every order.'],
            'partners'    => $this->with_images(is_array($rows) ? $rows : [], 400, 240, 'R'),
            'unavailable' => $rows === false,
        ]);
    }

    /**
     * Rows + image_url ('' when the file is missing).
     */
    protected function with_images(array $rows, int $width, int $height, string $option): array
    {
        return array_map(static fn ($row) => $row + ['image_url' => get_image(self::IMAGE_FOLDER, $row['photo'], $width, $height, $option)], $rows);
    }
}
