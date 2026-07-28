import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_client.dart';
import '../../../core/network/api_exception.dart';

class JbLudoRepository {
  JbLudoRepository(this._dio);

  final Dio _dio;

  Future<Map<String, dynamic>?> fetchMyProfile() async {
    final response = await _dio.get('/jb-ludo/profile/me');
    final envelope = parseApiData(response.data);
    final data = envelope['data'];
    if (data == null) return null;
    return Map<String, dynamic>.from(data as Map);
  }

  Future<Map<String, dynamic>> saveProfile(Map<String, dynamic> payload) async {
    final response = await _dio.post('/jb-ludo/profile', data: payload);
    final envelope = parseApiData(response.data);
    return Map<String, dynamic>.from(envelope['data'] as Map);
  }

  Future<Map<String, dynamic>> quickMatch() async {
    final response = await _dio.post('/jb-ludo/matches/quick');
    final envelope = parseApiData(response.data);
    return Map<String, dynamic>.from(envelope['data'] as Map);
  }

  Future<void> leaveQueue() async {
    await _dio.post('/jb-ludo/matches/queue/leave');
  }

  Future<Map<String, dynamic>> invite(int toPlayerId) async {
    final response = await _dio.post('/jb-ludo/matches/invite', data: {
      'to_player_id': toPlayerId,
    });
    final envelope = parseApiData(response.data);
    return Map<String, dynamic>.from(envelope['data'] as Map);
  }

  Future<Map<String, dynamic>> acceptInvite(int inviteId) async {
    final response = await _dio.post('/jb-ludo/matches/invites/$inviteId/accept');
    final envelope = parseApiData(response.data);
    return Map<String, dynamic>.from(envelope['data'] as Map);
  }

  Future<Map<String, dynamic>> fetchMatch(int id) async {
    final response = await _dio.get('/jb-ludo/matches/$id');
    final envelope = parseApiData(response.data);
    return Map<String, dynamic>.from(envelope['data'] as Map);
  }

  Future<Map<String, dynamic>> playMove(int matchId, List<Map<String, int>> path) async {
    final response = await _dio.post('/jb-ludo/matches/$matchId/moves', data: {'path': path});
    final envelope = parseApiData(response.data);
    return Map<String, dynamic>.from(envelope['data'] as Map);
  }

  Future<Map<String, dynamic>> resign(int matchId) async {
    final response = await _dio.post('/jb-ludo/matches/$matchId/resign');
    final envelope = parseApiData(response.data);
    return Map<String, dynamic>.from(envelope['data'] as Map);
  }

  Future<Map<String, dynamic>> reconnect(int matchId) async {
    final response = await _dio.post('/jb-ludo/matches/$matchId/reconnect');
    final envelope = parseApiData(response.data);
    return Map<String, dynamic>.from(envelope['data'] as Map);
  }

  Future<Map<String, dynamic>> disconnect(int matchId) async {
    final response = await _dio.post('/jb-ludo/matches/$matchId/disconnect');
    final envelope = parseApiData(response.data);
    return Map<String, dynamic>.from(envelope['data'] as Map);
  }

  Future<List<Map<String, dynamic>>> leaderboard() async {
    final response = await _dio.get('/jb-ludo/leaderboard');
    final envelope = parseApiData(response.data);
    return (envelope['data'] as List).cast<Map>().map((e) => Map<String, dynamic>.from(e)).toList();
  }

  Future<List<Map<String, dynamic>>> history() async {
    final response = await _dio.get('/jb-ludo/matches/history');
    final envelope = parseApiData(response.data);
    return (envelope['data'] as List).cast<Map>().map((e) => Map<String, dynamic>.from(e)).toList();
  }

  Future<List<Map<String, dynamic>>> searchPlayers(String q) async {
    final response = await _dio.get('/jb-ludo/players/search', queryParameters: {'q': q});
    final envelope = parseApiData(response.data);
    return (envelope['data'] as List).cast<Map>().map((e) => Map<String, dynamic>.from(e)).toList();
  }
}

final jbLudoRepositoryProvider = Provider<JbLudoRepository>(
  (ref) => JbLudoRepository(ref.watch(dioProvider)),
);
