<?php

/*
 * This file is part of the overtrue/wechat.
 *
 * (c) overtrue <i@overtrue.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace EasyWeChat\Tests\Notice;

use EasyWeChat\Core\Exceptions\InvalidArgumentException;
use EasyWeChat\Notice\Notice;
use EasyWeChat\Tests\TestCase;

class NoticeNoticeTest extends TestCase
{
    public function getNotice($mockHttp = false)
    {
        if ($mockHttp) {
            $accessToken = \Mockery::mock('EasyWeChat\Core\AccessToken');
            $accessToken->shouldReceive('getQueryFields')->andReturn(['access_token' => 'foo']);
            $notice = new Notice($accessToken);
            $http = \Mockery::mock('EasyWeChat\Core\Http[json]');
            $http->shouldReceive('json')->andReturnUsing(function ($api, $params) {
                return json_encode(compact('api', 'params'));
            });
            $notice->setHttp($http);

            return $notice;
        }
        $notice = \Mockery::mock('EasyWeChat\Notice\Notice[parseJSON]', [\Mockery::mock('EasyWeChat\Core\AccessToken')]);
        $notice->shouldReceive('parseJSON')->andReturnUsing(function ($api, $params) {
            if (isset($params[1])) {
                return ['api' => $params[0], 'params' => $params[1]];
            }

            return ['api' => $params[0]];
        });

        return $notice;
    }

    /**
     * Test setIndustry().
     */
    public function testSetIndustry()
    {
        $notice = $this->getNotice();

        $response = $notice->setIndustry('foo', 'bar');

        $this->assertStringStartsWith(Notice::API_SET_INDUSTRY, $response['api']);
        $this->assertEquals('foo', $response['params']['industry_id1']);
        $this->assertEquals('bar', $response['params']['industry_id2']);
    }

    /**
     * Test getIndustry().
     */
    public function testGetIndustry()
    {
        $notice = $this->getNotice();

        $response = $notice->getIndustry();

        $this->assertStringStartsWith(Notice::API_GET_INDUSTRY, $response['api']);
    }

    /**
     * Test addTemplate().
     */
    public function testAddTemplate()
    {
        $notice = $this->getNotice();

        $response = $notice->addTemplate('foo');

        $this->assertStringStartsWith(Notice::API_ADD_TEMPLATE, $response['api']);
        $this->assertEquals('foo', $response['params']['template_id_short']);
    }

    /**
     * Test getPrivateTemplates().
     */
    public function testGetPrivateTemplates()
    {
        $notice = $this->getNotice();

        $response = $notice->getPrivateTemplates();

        $this->assertStringStartsWith(Notice::API_GET_ALL_PRIVATE_TEMPLATE, $response['api']);
    }

    /**
     * Test deletePrivateTemplate().
     */
    public function testDeletePrivateTemplate()
    {
        $notice = $this->getNotice();

        $response = $notice->deletePrivateTemplate('foo');

        $this->assertStringStartsWith(Notice::API_DEL_PRIVATE_TEMPLATE, $response['api']);
        $this->assertEquals('foo', $response['params']['template_id']);
    }

    /**
     * Test send().
     */
    public function testSend()
    {
        $notice = $this->getNotice(true);

        try {
            $notice->send();
        } catch (\Exception $e) {
            $this->assertInstanceOf(InvalidArgumentException::class, $e);
            $this->assertContains(' can not be empty!', $e->getMessage());
        }

        $response = $notice->send(['touser' => 'foo', 'template_id' => 'bar']);

        $this->assertStringStartsWith(Notice::API_SEND_NOTICE, $response['api']);
        $this->assertEquals('foo', $response['params']['touser']);
        $this->assertEquals('bar', $response['params']['template_id']);
        // $this->assertEquals('#FF0000', $response['params']['topcolor']); // ????????????????????? https://github.com/overtrue/wechat/pull/595
        $this->assertEquals([], $response['params']['data']);

        $response = $notice->withTo('anzhengchao1')->withTemplateId('test_tpl_id')->withUrl('url')->withColor('color')->send();

        $this->assertEquals('anzhengchao1', $response['params']['touser']);
        $this->assertEquals('test_tpl_id', $response['params']['template_id']);
        $this->assertEquals('url', $response['params']['url']);
        // $this->assertEquals('color', $response['params']['topcolor']);

        $response = $notice->foo('bar')->withReceiver('anzhengchao2')->withTemplate('tpl1')->withLink('link')->andColor('andColor')->send();

        $this->assertEquals('anzhengchao2', $response['params']['touser']);
        $this->assertEquals('tpl1', $response['params']['template_id']);
        $this->assertEquals('link', $response['params']['url']);
        // $this->assertEquals('andColor', $response['params']['topcolor']);
    }

    /**
     * Test formatData().
     */
    public function testFormatData()
    {
        $notice = $this->getNotice(true);

        $data = [
            'first' => '????????????????????????',
            'keynote1' => '?????????',
            'keynote2' => '39.8???',
            'keynote3' => '2014???9???16???',
            'remark' => '?????????????????????',
        ];
        $response = $notice->to('anzhengchao')->color('color1')->template('overtrue')->data($data)->send();

        $this->assertEquals('anzhengchao', $response['params']['touser']);
        // $this->assertEquals('color1', $response['params']['topcolor']);
        $this->assertEquals('overtrue', $response['params']['template_id']);

        // format1
        $this->assertEquals(['value' => '????????????????????????', 'color' => '#173177'], $response['params']['data']['first']);
        $this->assertEquals(['value' => '?????????', 'color' => '#173177'], $response['params']['data']['keynote1']);
        $this->assertEquals(['value' => '39.8???', 'color' => '#173177'], $response['params']['data']['keynote2']);
        $this->assertEquals(['value' => '2014???9???16???', 'color' => '#173177'], $response['params']['data']['keynote3']);
        $this->assertEquals(['value' => '?????????????????????', 'color' => '#173177'], $response['params']['data']['remark']);

        // format2
        $data = [
            'first' => ['????????????????????????', '#555555'],
            'keynote1' => ['?????????', '#336699'],
            'keynote2' => ['39.8???'],
            'keynote3' => ['2014???9???16???', '#888888'],
            'remark' => '?????????????????????',
            'abc' => new \stdClass(),
        ];

        $response = $notice->to('anzhengchao')->color('color1')->template('overtrue')->data($data)->send();

        $this->assertEquals(['value' => '????????????????????????', 'color' => '#555555'], $response['params']['data']['first']);
        $this->assertEquals(['value' => '?????????', 'color' => '#336699'], $response['params']['data']['keynote1']);
        $this->assertEquals(['value' => '39.8???', 'color' => '#173177'], $response['params']['data']['keynote2']);
        $this->assertEquals(['value' => '2014???9???16???', 'color' => '#888888'], $response['params']['data']['keynote3']);
        $this->assertEquals(['value' => '?????????????????????', 'color' => '#173177'], $response['params']['data']['remark']);
        $this->assertEquals(['value' => 'error data item.', 'color' => '#173177'], $response['params']['data']['abc']);

        // format3
        $data = [
            'first' => ['value' => '????????????????????????', 'color' => '#555555'],
            'keynote1' => ['value' => '?????????', 'color' => '#336699'],
            'keynote2' => ['value' => '39.8???', 'color' => '#FF0000'],
            'keynote3' => ['value' => '2014???9???16???', 'color' => '#888888'],
            'remark' => ['value' => '?????????????????????', 'color' => '#5599FF'],
        ];
        $response = $notice->to('anzhengchao')->color('color1')->template('overtrue')->data($data)->send();

        $this->assertEquals(['value' => '????????????????????????', 'color' => '#555555'], $response['params']['data']['first']);
        $this->assertEquals(['value' => '?????????', 'color' => '#336699'], $response['params']['data']['keynote1']);
        $this->assertEquals(['value' => '39.8???', 'color' => '#FF0000'], $response['params']['data']['keynote2']);
        $this->assertEquals(['value' => '2014???9???16???', 'color' => '#888888'], $response['params']['data']['keynote3']);
        $this->assertEquals(['value' => '?????????????????????', 'color' => '#5599FF'], $response['params']['data']['remark']);
    }
}
