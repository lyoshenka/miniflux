<?php

require '../common.php';

use Model\Feed;
use PicoDb\Database;

// Route handler
function route($name, Closure $callback = null)
{
    static $routes = array();

    if ($callback !== null) {
        $routes[$name] = $callback;
    }
    else if (isset($routes[$name])) {
        $routes[$name]();
    }
}

// Serialize the payload in Json (XML is not supported)
function response(array $response)
{
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Fever authentication
function auth()
{
    $credentials = Database::get('db')->table('config')
                       ->columns('username', 'fever_token')
                       ->findOne();

    $api_key = md5($credentials['username'].':'.$credentials['fever_token']);

    $response = array(
        'api_version' => 3,
        'auth' => (int) (@$_POST['api_key'] === $api_key),
        'last_refreshed_on_time' => time(),
    );

    return $response;
}

// Call: ?api&groups
route('groups', function() {

    $response = auth();

    if ($response['auth']) {

        $feed_ids = Database::get('db')
                        ->table('feeds')
                        ->findAllByColumn('id');

        $response['groups'] = array(
            array(
                'id' => 1,
                'title' => t('All'),
            )
        );

        $response['feeds_groups'] = array(
            array(
                'group_id' => 1,
                'feed_ids' => implode(',', $feed_ids),
            )
        );
    }

    response($response);
});

// Call: ?api&feeds
route('feeds', function() {

    $response = auth();

    if ($response['auth']) {

        $response['feeds'] = array();
        $feeds = Feed\get_all();
        $feed_ids = array();

        foreach ($feeds as $feed) {
            $response['feeds'][] = array(
                'id' => (int) $feed['id'],
                'favicon_id' => 1,
                'title' => $feed['title'],
                'url' => $feed['feed_url'],
                'site_url' => $feed['site_url'],
                'is_spark' => 0,
                'last_updated_on_time' => $feed['last_checked'] ?: time(),
            );

            $feed_ids[] = $feed['id'];
        }

        $response['feeds_groups'] = array(
            array(
                'group_id' => 1,
                'feed_ids' => implode(',', $feed_ids),
            )
        );
    }

    response($response);
});

// Call: ?api&favicons
route('favicons', function() {

    $response = auth();

    if ($response['auth']) {
        $response['favicons'] = array();
    }

    response($response);
});

// Call: ?api&items
route('items', function() {

    $response = auth();

    if ($response['auth']) {

        $query = Database::get('db')
                        ->table('items')
                        ->limit(50)
                        ->columns(
                            'rowid',
                            'feed_id',
                            'title',
                            'author',
                            'content',
                            'url',
                            'updated',
                            'status',
                            'bookmark'
                        );

        if (isset($_GET['since_id']) && is_numeric($_GET['since_id'])) {

            $items = $query->gt('rowid', $_GET['since_id'])
                           ->asc('rowid');
        }
        else if (! empty($_GET['with_ids'])) {
            $query->in('rowid', explode(',', $_GET['with_ids']));
        }

        $items = $query->findAll();
        $response['items'] = array();

        foreach ($items as $item) {
            $response['items'][] = array(
                'id' => (int) $item['rowid'],
                'feed_id' => (int) $item['feed_id'],
                'title' => $item['title'],
                'author' => $item['author'],
                'html' => $item['content'],
                'url' => $item['url'],
                'is_saved' => (int) $item['bookmark'],
                'is_read' => $item['status'] == 'read' ? 1 : 0,
                'created_on_time' => $item['updated'],
            );
        }

        $response['total_items'] = Database::get('db')
                                        ->table('items')
                                        ->neq('status', 'removed')
                                        ->count();
    }

    response($response);
});

// Call: ?api&links
route('links', function() {

    $response = auth();

    if ($response['auth']) {
        $response['links'] = array();
    }

    response($response);
});

// Call: ?api&unread_item_ids
route('unread_item_ids', function() {

    $response = auth();

    if ($response['auth']) {

        $item_ids = Database::get('db')
                    ->table('items')
                    ->eq('status', 'unread')
                    ->findAllByColumn('rowid');

        $response['unread_item_ids'] = implode(',', $item_ids);
    }

    response($response);
});

// Call: ?api&saved_item_ids
route('saved_item_ids', function() {

    $response = auth();

    if ($response['auth']) {

        $item_ids = Database::get('db')
                    ->table('items')
                    ->eq('bookmark', 1)
                    ->findAllByColumn('rowid');

        $response['saved_item_ids'] = implode(',', $item_ids);
    }

    response($response);
});

// handle write items
route('write_items', function() {

    $response = auth();

    if ($response['auth']) {

        $query = Database::get('db')
                    ->table('items')
                    ->eq('rowid', $_POST['id']);

        if ($_POST['as'] === 'saved') {
            $query->update(array('bookmark' => 1));
        }
        else if ($_POST['as'] === 'unsaved') {
            $query->update(array('bookmark' => 0));
        }
        else if ($_POST['as'] === 'read') {
            $query->update(array('status' => 'read'));
        }
        else if ($_POST['as'] === 'unread') {
            $query->update(array('status' => 'unread'));
        }
    }

    response($response);
});

// handle write feeds
route('write_feeds', function() {

    $response = auth();

    if ($response['auth']) {

        Database::get('db')
            ->table('items')
            ->eq('feed_id', $_POST['id'])
            ->lte('updated', $_POST['before'])
            ->update(array('status' => $_POST['as'] === 'read' ? 'read' : 'unread'));
    }

    response($response);
});

// handle write groups
route('write_groups', function() {

    $response = auth();

    if ($response['auth']) {

        Database::get('db')
            ->table('items')
            ->lte('updated', $_POST['before'])
            ->update(array('status' => $_POST['as'] === 'read' ? 'read' : 'unread'));
    }

    response($response);
});

foreach (array_keys($_GET) as $action) {
    route($action);
}

if (! empty($_POST['mark']) && ! empty($_POST['as'])
    && ! is_null(filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT, array('options' => array('default' => NULL,'min_range' => -1)))) ){

    if ($_POST['mark'] === 'item') {
        route('write_items');
    }
    else if ($_POST['mark'] === 'feed' && ! empty($_POST['before'])) {
        route('write_feeds');
    }
    else if ($_POST['mark'] === 'group' && ! empty($_POST['before'])) {
        route('write_groups');
    }
}

response(auth());
